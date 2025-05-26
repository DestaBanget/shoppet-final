pipeline {
    agent any

    environment {
        IMAGE_NAME = "php-web-app"
        TAG = "${BUILD_NUMBER}"
        KUBECONFIG = "/var/lib/jenkins/.kube/config"
    }

    stages {
        stage('Checkout') {
            steps {
                checkout scm
            }
        }

        stage('Build Docker Image') {
            steps {
                sh 'docker build -t ${IMAGE_NAME}:${TAG} .'
            }
        }

        stage('Update Kubernetes Manifests') {
            steps {
                sh '''
                    sed -i "s|image:.*php-web-app.*|image: ${IMAGE_NAME}:${TAG}|" kubernetes/deployment.yaml
                    sed -i "s|imagePullPolicy:.*|imagePullPolicy: Never|" kubernetes/deployment.yaml
                '''
            }
        }

        stage('Deploy to Kubernetes') {
            steps {
                sh '''
			kubectl apply --insecure-skip-tls-verify -f kubernetes/mysql-deployment.yaml
			kubectl apply --insecure-skip-tls-verify -f kubernetes/nginx-config.yaml
			kubectl apply --insecure-skip-tls-verify -f kubernetes/deployment.yaml
			kubectl apply --insecure-skip-tls-verify -f kubernetes/service.yaml

                '''
            }
        }
    }

    post {
        success {
            echo "✅ Deployment sukses dengan image tag ${TAG}"
        }
        failure {
            echo "❌ Deployment gagal"
        }
    }
}
