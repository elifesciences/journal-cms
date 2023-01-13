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
        stage 'End2end tests', {
            elifeSpectrum(
                deploy: [
                    stackname: 'journal-cms--end2end',
                    revision: commit,
                    folder: '/srv/journal-cms',
                    rollbackStep: {
                        builderCmd 'journal-cms--end2end', 'cd /srv/journal-cms/web && ../vendor/bin/drush si config_installer -y && ../vendor/bin/drush cr'
                        builderDeployRevision 'journal-cms--end2end', 'approved'
                        builderSmokeTests 'journal-cms--end2end', '/srv/journal-cms'
                    }
                ],
                marker: 'journal_cms'
            )
        }

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
