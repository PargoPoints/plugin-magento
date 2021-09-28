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
                withCredentials([
                        sshUserPrivateKey(credentialsId: 'pargo-magento-2-4-private-key',keyFileVariable: 'MAGENTO_24_PRIVATE_KEY',passphraseVariable: '',usernameVariable: ''),
                        file(credentialsId: 'pargo-ansible-vault', variable: 'VAULT_KEY')
                        ])
                    {
                    sh 'set +x'
                    sh 'ansible-playbook -i playbooks/subprod/inventory.yml -l magento_2_4 -e plugin_version_tag=dev-$GIT_BRANCH playbooks/subprod/install-magento-plugin.yml --private-key=$MAGENTO_24_PRIVATE_KEY --vault-password-file=$VAULT_KEY'
                    }
            }
        }
    }
}