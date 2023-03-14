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
        stage('Wait for Packagist update') {
            agent any
            when {
                anyOf {
                    branch 'staging'
                }
            }
            steps {
                    sh "pip3 install urllib"
                    sh "python3 ci/packagist_check.py $GIT_BRANCH $GIT_COMMIT"
            }
        }
        stage('Run Playbook 2.4.4') {
            when {
                anyOf {
                    branch 'staging'
                }
            }
            agent any
            steps {
                    ansiblePlaybook credentialsId: 'pargo-magento-2-4-4-private-key',
                    disableHostKeyChecking: true,
                    inventory: 'playbooks/subprod/inventory.yml',
                    playbook: 'playbooks/subprod/install-magento-plugin.yml',
                    limit: 'magento_2_4_4',
                    vaultCredentialsId: 'pargo-ansible-vault',
                    extras: "-e plugin_version_tag=dev-$GIT_BRANCH"
                    }
            post {
                success {
                    slackSend(channel: '#eng-builds',
                message: "Magento Staging 2.4.4 Deploy - SUCCESSFUL: \nBuild #: ${env.BUILD_NUMBER} - (<${env.BUILD_URL}|Open>) \nBranch: ${env.BRANCH_NAME} \nView Changes in this build: ${RUN_CHANGES_DISPLAY_URL}",
                color: 'good')
                }
                failure {
                    slackSend(channel: '#eng-builds',
        message: "Magento Staging 2.4.4 Deploy - FAILED: \nBuild #: ${env.BUILD_NUMBER} \nLink: (<${env.BUILD_URL}|Open>)",
        color: 'danger')
                }
            }
            }
        }
}