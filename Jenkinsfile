pipeline {
    agent any

    environment {
        IMAGE_NAME = "php-web-app"
        TAG = "${BUILD_NUMBER}"
    }

    stages {
        stage('Checkout') {
            steps {
                checkout scm
            }
        }

        stage('Build Docker Image Locally') {
            steps {
                sh 'docker build -t ${IMAGE_NAME}:${TAG} .'
            }
        }

        stage('Update Kubernetes Manifests') {
            steps {
                // Ganti tag image dan pastikan imagePullPolicy diset Never
                sh '''
                    sed -i "s|image:.*php-web-app.*|image: ${IMAGE_NAME}:${TAG}\\n          imagePullPolicy: Never|" kubernetes/deployment.yaml
                '''
            }
        }

        stage('Deploy to Kubernetes') {
            steps {
                sh '''
                    kubectl apply -f kubernetes/nginx-config.yaml
                    kubectl apply -f kubernetes/deployment.yaml
                    kubectl apply -f kubernetes/service.yaml
                '''
            }
        }
    }

    post {
        success {
            echo "✅ Web App deployed successfully!"
        }
        failure {
            echo "❌ Deployment failed."
        }
    }
}
