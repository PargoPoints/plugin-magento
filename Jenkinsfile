pipeline {
    agent none
    stages {
        stage('Clone Playbooks') {
            when {
                anyOf {
                    branch 'staging'
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
        }
}