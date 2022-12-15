pipeline {
    agent none
    stages {
        stage('Clone Playbooks') {
            when {
                anyOf {
                    branch 'feat/polling-test'
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
        stage('Wait for Packagist update') {
            when {
                anyOf {
                    branch 'staging'
                }
            }
            agent any
            steps {
                    sh "python3 ci/packagist_check.py $GIT_BRANCH $GIT_COMMIT"
            }
        stage('Run Playbook 2.4') {
            when {
                anyOf {
                    branch 'staging'
                }
            }
            agent any
            steps {
                    ansiblePlaybook credentialsId: 'pargo-magento-2-4-private-key',
                    disableHostKeyChecking: true,
                    inventory: 'playbooks/subprod/inventory.yml',
                    playbook: 'playbooks/subprod/install-magento-plugin.yml',
                    limit: 'magento_2_4',
                    vaultCredentialsId: 'pargo-ansible-vault',
                    extras: "-e plugin_version_tag=dev-$GIT_BRANCH"
                    }
            }
        stage('Run Playbook 2.3') {
            when {
                anyOf {
                    branch 'staging'
                }
            }
            agent any
            steps {
                    ansiblePlaybook credentialsId: 'pargo-magento-2-3-private-key',
                    disableHostKeyChecking: true,
                    inventory: 'playbooks/subprod/inventory.yml',
                    playbook: 'playbooks/subprod/install-magento-plugin.yml',
                    limit: 'magento_2_3',
                    vaultCredentialsId: 'pargo-ansible-vault',
                    extras: "-e plugin_version_tag=dev-$GIT_BRANCH"
                    }
            }
        }
}