elifePipeline {
    def commit
    stage 'Checkout', {
        checkout scm
        commit = elifeGitRevision()
    }

    stage 'Project tests', {
        lock('journal-cms--ci') {
            builderDeployRevision 'journal-cms--ci', commit
            builderProjectTests 'journal-cms--ci', '/srv/journal-cms', ['build/phpunit.xml']
        }
    }

    elifeMainlineOnly {
        
        stage 'Deploy on continuumtest', {
            lock('journal-cms--continuumtest') {
                builderDeployRevision 'journal-cms--continuumtest', commit
                builderSmokeTests 'journal-cms--continuumtest', '/srv/journal-cms'
            }
        }

        stage 'Approval', {
            elifeGitMoveToBranch commit, 'approved'
        }
    }
}
