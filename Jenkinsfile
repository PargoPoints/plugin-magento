pipeline {
    agent none
    environment {
        AWS_CREDENTIALS_ID = 'pargo-jenkins-aws-credentials'
        STAGING_APP_CREDENTIALS_PATH = '/pargo/staging/magento-2-4/'
        AWS_REGION = 'eu-west-1'
    }
    stages {
        stage('Clone Playbooks') {
            when {
                anyOf {
                    branch 'staging'
                    branch 'feat/deploy-pipeline'
                }
            }
            agent any
            steps {
                dir('playbooks') {
                git branch: 'main',
                credentialsId: 'pargo-jenkins-github-organisation-test',
                url: 'https://github.com/PargoPoints/devops-ansible.git'
                }
            }
        }
        stage('Run Playbook') {
            when {
                anyOf {
                    branch 'staging'
                    branch 'feat/deploy-pipeline'
                }
            }
            agent any
            steps {
                    ansiblePlaybook credentialsId: 'pargo-magento-2-4-private-key', inventory: 'playbooks/subprod/inventory.yml', playbook: 'playbooks/subprod/install-magento-plugin.yml', limit: 'magento_2_4', vaultCredentialsId: 'pargo-ansible-vault', extras: '-e plugin_version_tag=$VERSION'
                    }
            }
        }
}