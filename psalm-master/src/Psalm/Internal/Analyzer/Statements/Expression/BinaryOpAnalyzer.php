<?php

declare(strict_types=1);

namespace Psalm\Internal\Analyzer\Statements\Expression;

use PhpParser;
use Psalm\CodeLocation;
use Psalm\Context;
use Psalm\Internal\Analyzer\FunctionLikeAnalyzer;
use Psalm\Internal\Analyzer\Statements\Expression\BinaryOp\AndAnalyzer;
use Psalm\Internal\Analyzer\Statements\Expression\BinaryOp\CoalesceAnalyzer;
use Psalm\Internal\Analyzer\Statements\Expression\BinaryOp\ConcatAnalyzer;
use Psalm\Internal\Analyzer\Statements\Expression\BinaryOp\NonComparisonOpAnalyzer;
use Psalm\Internal\Analyzer\Statements\Expression\BinaryOp\OrAnalyzer;
use Psalm\Internal\Analyzer\Statements\ExpressionAnalyzer;
use Psalm\Internal\Analyzer\StatementsAnalyzer;
use Psalm\Internal\Codebase\VariableUseGraph;
use Psalm\Internal\DataFlow\DataFlowNode;
use Psalm\Internal\MethodIdentifier;
use Psalm\Issue\DocblockTypeContradiction;
use Psalm\Issue\ImpureMethodCall;
use Psalm\Issue\InvalidOperand;
use Psalm\Issue\RedundantCondition;
use Psalm\Issue\RedundantConditionGivenDocblockType;
use Psalm\Issue\TypeDoesNotContainType;
use Psalm\IssueBuffer;
use Psalm\Plugin\EventHandler\Event\AddRemoveTaintsEvent;
use Psalm\Type;
use Psalm\Type\Atomic\TLiteralInt;
use Psalm\Type\Atomic\TLiteralString;
use Psalm\Type\Atomic\TNamedObject;
use Psalm\Type\Union;
use UnexpectedValueException;

use function strlen;

/**
 * @internal
 */
final class BinaryOpAnalyzer
{
    public static function analyze(
        StatementsAnalyzer $statements_analyzer,
        PhpParser\Node\Expr\BinaryOp $stmt,
        Context $context,
        int $nesting = 0,
        bool $from_stmt = false,
    ): bool {
        if ($stmt instanceof PhpParser\Node\Expr\BinaryOp\Concat && $nesting > 100) {
            $statements_analyzer->node_data->setType($stmt, Type::getString());

            // ignore deeply-nested string concatenation
            return true;
        }

        if ($stmt instanceof PhpParser\Node\Expr\BinaryOp\BooleanAnd ||
            $stmt instanceof PhpParser\Node\Expr\BinaryOp\LogicalAnd
        ) {
            $was_inside_general_use = $context->inside_general_use;
            $context->inside_general_use = true;

            $expr_result = AndAnalyzer::analyze(
                $statements_analyzer,
                $stmt,
                $context,
                $from_stmt,
            );

            $context->inside_general_use = $was_inside_general_use;

            $statements_analyzer->node_data->setType($stmt, Type::getBool());

            return $expr_result;
        }

        if ($stmt instanceof PhpParser\Node\Expr\BinaryOp\BooleanOr ||
            $stmt instanceof PhpParser\Node\Expr\BinaryOp\LogicalOr
        ) {
            $was_inside_general_use = $context->inside_general_use;
            $context->inside_general_use = true;

            $expr_result = OrAnalyzer::analyze(
                $statements_analyzer,
                $stmt,
                $context,
                $from_stmt,
            );

            $context->inside_general_use = $was_inside_general_use;

            $statements_analyzer->node_data->setType($stmt, Type::getBool());

            return $expr_result;
        }

        if ($stmt instanceof PhpParser\Node\Expr\BinaryOp\Coalesce) {
            $expr_result = CoalesceAnalyzer::analyze(
                $statements_analyzer,
                $stmt,
                $context,
            );

            self::addDataFlow(
                $statements_analyzer,
                $stmt,
                $stmt->left,
                $stmt->right,
                'coalesce',
            );

            return $expr_result;
        }

        if ($stmt->left instanceof PhpParser\Node\Expr\BinaryOp) {
            if (self::analyze($statements_analyzer, $stmt->left, $context, $nesting + 1) === false) {
                return false;
            }
        } else {
            if (ExpressionAnalyzer::analyze($statements_analyzer, $stmt->left, $context) === false) {
                return false;
            }
        }

        if ($stmt->right instanceof PhpParser\Node\Expr\BinaryOp) {
            if (self::analyze($statements_analyzer, $stmt->right, $context, $nesting + 1) === false) {
                return false;
            }
        } else {
            if (ExpressionAnalyzer::analyze($statements_analyzer, $stmt->right, $context) === false) {
                return false;
            }
        }

        if ($stmt instanceof PhpParser\Node\Expr\BinaryOp\Concat) {
            $stmt_type = Type::getString();

            ConcatAnalyzer::analyze(
                $statements_analyzer,
                $stmt->left,
                $stmt->right,
                $context,
                $result_type,
            );

            if ($result_type) {
                $stmt_type = $result_type;
            }

            if ($graph = $statements_analyzer->getDataFlowGraphWithSuppressed()) {
                $stmt_left_type = $statements_analyzer->node_data->getType($stmt->left);
                $stmt_right_type = $statements_analyzer->node_data->getType($stmt->right);

                $var_location = new CodeLocation($statements_analyzer, $stmt);

                $new_parent_node = DataFlowNode::getForAssignment('concat', $var_location);
                $graph->addNode($new_parent_node);

                $stmt_type = $stmt_type->setParentNodes([
                    $new_parent_node->id => $new_parent_node,
                ]);

                $codebase = $statements_analyzer->getCodebase();
                $event = new AddRemoveTaintsEvent($stmt, $context, $statements_analyzer, $codebase);

                $added_taints = $codebase->config->eventDispatcher->dispatchAddTaints($event);
                $removed_taints = $codebase->config->eventDispatcher->dispatchRemoveTaints($event);

                $taints = $added_taints & ~$removed_taints;
                if ($taints !== 0 && !$graph instanceof VariableUseGraph) {
                    $taint_source = $new_parent_node->setTaints($taints);
                    $graph->addSource($taint_source);
                }

                if ($stmt_left_type && $stmt_left_type->parent_nodes) {
                    foreach ($stmt_left_type->parent_nodes as $parent_node) {
                        $graph->addPath(
                            $parent_node,
                            $new_parent_node,
                            'concat',
                            $added_taints,
                            $removed_taints,
                        );
                    }
                }

                if ($stmt_right_type && $stmt_right_type->parent_nodes) {
                    foreach ($stmt_right_type->parent_nodes as $parent_node) {
                        $graph->addPath(
                            $parent_node,
                            $new_parent_node,
                            'concat',
                            $added_taints,
                            $removed_taints,
                        );
                    }
                }
            }

            $statements_analyzer->node_data->setType($stmt, $stmt_type);

            return true;
        }

        if ($stmt instanceof PhpParser\Node\Expr\BinaryOp\Spaceship) {
            $statements_analyzer->node_data->setType(
                $stmt,
                new Union(
                    [
                        new TLiteralInt(-1),
                        new TLiteralInt(0),
                        new TLiteralInt(1),
                    ],
                ),
            );

            self::addDataFlow(
                $statements_analyzer,
                $stmt,
                $stmt->left,
                $stmt->right,
                '<=>',
            );

            return true;
        }

        if ($stmt instanceof PhpParser\Node\Expr\BinaryOp\Equal
            || $stmt instanceof PhpParser\Node\Expr\BinaryOp\NotEqual
            || $stmt instanceof PhpParser\Node\Expr\BinaryOp\Identical
            || $stmt instanceof PhpParser\Node\Expr\BinaryOp\NotIdentical
            || $stmt instanceof PhpParser\Node\Expr\BinaryOp\Greater
            || $stmt instanceof PhpParser\Node\Expr\BinaryOp\GreaterOrEqual
            || $stmt instanceof PhpParser\Node\Expr\BinaryOp\Smaller
            || $stmt instanceof PhpParser\Node\Expr\BinaryOp\SmallerOrEqual
        ) {
            $statements_analyzer->node_data->setType($stmt, Type::getBool());

            $stmt_left_type = $statements_analyzer->node_data->getType($stmt->left);
            $stmt_right_type = $statements_analyzer->node_data->getType($stmt->right);

            if (($stmt instanceof PhpParser\Node\Expr\BinaryOp\Greater
                    || $stmt instanceof PhpParser\Node\Expr\BinaryOp\GreaterOrEqual
                    || $stmt instanceof PhpParser\Node\Expr\BinaryOp\Smaller
                    || $stmt instanceof PhpParser\Node\Expr\BinaryOp\SmallerOrEqual)
                && $statements_analyzer->getCodebase()->config->strict_binary_operands
                && $stmt_left_type
                && $stmt_right_type
                && (($stmt_left_type->isSingle() && $stmt_left_type->hasBool())
                    || ($stmt_right_type->isSingle() && $stmt_right_type->hasBool()))
            ) {
                IssueBuffer::maybeAdd(
                    new InvalidOperand(
                        'Cannot compare ' . $stmt_left_type->getId() . ' to ' . $stmt_right_type->getId(),
                        new CodeLocation($statements_analyzer, $stmt),
                    ),
                    $statements_analyzer->getSuppressedIssues(),
                );
            }

            if (($stmt instanceof PhpParser\Node\Expr\BinaryOp\Equal
                    || $stmt instanceof PhpParser\Node\Expr\BinaryOp\NotEqual
                    || $stmt instanceof PhpParser\Node\Expr\BinaryOp\Identical
                    || $stmt instanceof PhpParser\Node\Expr\BinaryOp\NotIdentical)
                && $stmt->left instanceof PhpParser\Node\Expr\FuncCall
                && $stmt->left->name instanceof PhpParser\Node\Name
                && $stmt->left->name->getParts() === ['substr']
                && isset($stmt->left->getArgs()[1])
                && $stmt_right_type
                && $stmt_right_type->hasLiteralString()
            ) {
                $from_type = $statements_analyzer->node_data->getType($stmt->left->getArgs()[1]->value);

                $length_type = isset($stmt->left->getArgs()[2])
                    ? ($statements_analyzer->node_data->getType($stmt->left->getArgs()[2]->value) ?? Type::getMixed())
                    : null;

                $string_length = null;

                if ($from_type && $from_type->isSingleIntLiteral() && $length_type === null) {
                    $string_length = -$from_type->getSingleIntLiteral()->value;
                } elseif ($length_type && $length_type->isSingleIntLiteral()) {
                    $string_length = $length_type->getSingleIntLiteral()->value;
                }

                if ($string_length > 0) {
                    foreach ($stmt_right_type->getAtomicTypes() as $atomic_right_type) {
                        if ($atomic_right_type instanceof TLiteralString) {
                            if (strlen($atomic_right_type->value) !== $string_length) {
                                if ($stmt instanceof PhpParser\Node\Expr\BinaryOp\Equal
                                    || $stmt instanceof PhpParser\Node\Expr\BinaryOp\Identical
                                ) {
                                    if ($atomic_right_type->from_docblock) {
                                        IssueBuffer::maybeAdd(
                                            new DocblockTypeContradiction(
                                                $atomic_right_type . ' string length is not ' . $string_length,
                                                new CodeLocation($statements_analyzer, $stmt),
                                                "strlen($atomic_right_type) !== $string_length",
                                            ),
                                            $statements_analyzer->getSuppressedIssues(),
                                        );
                                    } else {
                                        IssueBuffer::maybeAdd(
                                            new TypeDoesNotContainType(
                                                $atomic_right_type . ' string length is not ' . $string_length,
                                                new CodeLocation($statements_analyzer, $stmt),
                                                "strlen($atomic_right_type) !== $string_length",
                                            ),
                                            $statements_analyzer->getSuppressedIssues(),
                                        );
                                    }
                                } else {
                                    if ($atomic_right_type->from_docblock) {
                                        IssueBuffer::maybeAdd(
                                            new RedundantConditionGivenDocblockType(
                                                $atomic_right_type . ' string length is never ' . $string_length,
                                                new CodeLocation($statements_analyzer, $stmt),
                                                "strlen($atomic_right_type) !== $string_length",
                                            ),
                                            $statements_analyzer->getSuppressedIssues(),
                                        );
                                    } else {
                                        IssueBuffer::maybeAdd(
                                            new RedundantCondition(
                                                $atomic_right_type . ' string length is never ' . $string_length,
                                                new CodeLocation($statements_analyzer, $stmt),
                                                "strlen($atomic_right_type) !== $string_length",
                                            ),
                                            $statements_analyzer->getSuppressedIssues(),
                                        );
                                    }
                                }
                            }
                        }
                    }
                }
            }

            $codebase = $statements_analyzer->getCodebase();

            if ($stmt instanceof PhpParser\Node\Expr\BinaryOp\Equal
                && $stmt_left_type
                && $stmt_right_type
                && ($context->mutation_free || $codebase->alter_code)
            ) {
                self::checkForImpureEqualityComparison(
                    $statements_analyzer,
                    $stmt,
                    $stmt_left_type,
                    $stmt_right_type,
                );
            }

            self::addDataFlow(
                $statements_analyzer,
                $stmt,
                $stmt->left,
                $stmt->right,
                'comparison',
            );

            return true;
        }

        NonComparisonOpAnalyzer::analyze(
            $statements_analyzer,
            $stmt,
            $context,
        );

        return true;
    }

    public static function addDataFlow(
        StatementsAnalyzer $statements_analyzer,
        PhpParser\Node\Expr $stmt,
        PhpParser\Node\Expr $left,
        PhpParser\Node\Expr $right,
        string $type = 'binaryop',
    ): void {
        if ($stmt->getLine() === -1) {
            throw new UnexpectedValueException('bad');
        }
        $result_type = $statements_analyzer->node_data->getType($stmt);
        if (!$result_type) {
            return;
        }

        $graph = $statements_analyzer->data_flow_graph;
        if ($statements_analyzer->taint_flow_graph
            && $stmt instanceof PhpParser\Node\Expr\BinaryOp
            && !$stmt instanceof PhpParser\Node\Expr\BinaryOp\Concat
            && !$stmt instanceof PhpParser\Node\Expr\BinaryOp\Coalesce
            && (!$stmt instanceof PhpParser\Node\Expr\BinaryOp\Plus || !$result_type->hasArray())
        ) {
            $graph = $statements_analyzer->variable_use_graph;
            //among BinaryOp, only Concat and Coalesce can pass tainted value to the result. Also Plus on arrays only
        }

        if (!$graph) {
            return;
        }
            $stmt_left_type = $statements_analyzer->node_data->getType($left);
            $stmt_right_type = $statements_analyzer->node_data->getType($right);

            $var_location = new CodeLocation($statements_analyzer, $stmt);

            $new_parent_node = DataFlowNode::getForAssignment($type, $var_location);
            $graph->addNode($new_parent_node);

            $result_type = $result_type->setParentNodes([
                $new_parent_node->id => $new_parent_node,
            ]);
            $statements_analyzer->node_data->setType($stmt, $result_type);

        if ($stmt_left_type && $stmt_left_type->parent_nodes) {
            foreach ($stmt_left_type->parent_nodes as $parent_node) {
                $graph->addPath($parent_node, $new_parent_node, $type);
            }
        }

        if ($stmt_right_type && $stmt_right_type->parent_nodes) {
            foreach ($stmt_right_type->parent_nodes as $parent_node) {
                $graph->addPath($parent_node, $new_parent_node, $type);
            }
        }

        if ($stmt instanceof PhpParser\Node\Expr\AssignOp
                && $statements_analyzer->variable_use_graph
            ) {
            $root_expr = $left;

            while ($root_expr instanceof PhpParser\Node\Expr\ArrayDimFetch) {
                $root_expr = $root_expr->var;
            }

            if ($left instanceof PhpParser\Node\Expr\PropertyFetch) {
                $graph->addPath(
                    $new_parent_node,
                    DataFlowNode::getForVariableUse(),
                    'used-by-instance-property',
                );
            } if ($left instanceof PhpParser\Node\Expr\StaticPropertyFetch) {
                $graph->addPath(
                    $new_parent_node,
                    DataFlowNode::getForVariableUse(),
                    'use-in-static-property',
                );
            } elseif (!$left instanceof PhpParser\Node\Expr\Variable) {
                $graph->addPath(
                    $new_parent_node,
                    DataFlowNode::getForVariableUse(),
                    'variable-use',
                );
            }
        }
    }

    private static function checkForImpureEqualityComparison(
        StatementsAnalyzer $statements_analyzer,
        PhpParser\Node\Expr\BinaryOp\Equal $stmt,
        Union $stmt_left_type,
        Union $stmt_right_type,
    ): void {
        $codebase = $statements_analyzer->getCodebase();

        if ($stmt_left_type->hasString() && $stmt_right_type->hasObjectType()) {
            foreach ($stmt_right_type->getAtomicTypes() as $atomic_type) {
                if ($atomic_type instanceof TNamedObject) {
                    try {
                        $storage = $codebase->methods->getStorage(
                            new MethodIdentifier(
                                $atomic_type->value,
                                '__tostring',
                            ),
                        );
                    } catch (UnexpectedValueException) {
                        continue;
                    }

                    if (!$storage->mutation_free) {
                        if ($statements_analyzer->getSource()
                                instanceof FunctionLikeAnalyzer
                            && $statements_analyzer->getSource()->track_mutations
                        ) {
                            $statements_analyzer->getSource()->inferred_has_mutation = true;
                            $statements_analyzer->getSource()->inferred_impure = true;
                        } else {
                            IssueBuffer::maybeAdd(
                                new ImpureMethodCall(
                                    'Cannot call a possibly-mutating method '
                                        . $atomic_type->value . '::__toString from a pure context',
                                    new CodeLocation($statements_analyzer, $stmt),
                                ),
                                $statements_analyzer->getSuppressedIssues(),
                            );
                        }
                    }
                }
            }
        } elseif ($stmt_right_type->hasString() && $stmt_left_type->hasObjectType()) {
            foreach ($stmt_left_type->getAtomicTypes() as $atomic_type) {
                if ($atomic_type instanceof TNamedObject) {
                    try {
                        $storage = $codebase->methods->getStorage(
                            new MethodIdentifier(
                                $atomic_type->value,
                                '__tostring',
                            ),
                        );
                    } catch (UnexpectedValueException) {
                        continue;
                    }

                    if (!$storage->mutation_free) {
                        if ($statements_analyzer->getSource() instanceof FunctionLikeAnalyzer
                            && $statements_analyzer->getSource()->track_mutations
                        ) {
                            $statements_analyzer->getSource()->inferred_has_mutation = true;
                            $statements_analyzer->getSource()->inferred_impure = true;
                        } else {
                            IssueBuffer::maybeAdd(
                                new ImpureMethodCall(
                                    'Cannot call a possibly-mutating method '
                                        . $atomic_type->value . '::__toString from a pure context',
                                    new CodeLocation($statements_analyzer, $stmt),
                                ),
                                $statements_analyzer->getSuppressedIssues(),
                            );
                        }
                    }
                }
            }
        }
    }
}
