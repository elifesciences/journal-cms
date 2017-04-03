elifePipeline {
    stage 'Checkout'
    checkout scm
    def commit = elifeGitRevision()

    stage 'Project tests'
    lock('journal-cms--ci') {
        builderDeployRevision 'journal-cms--ci', commit
        builderProjectTests 'journal-cms--ci', '/srv/journal-cms', ['build/phpunit.xml']
    }

    elifeMainlineOnly {
        stage 'End2end tests'
        elifeSpectrum(
            deploy: [
                stackname: 'journal-cms--end2end',
                revision: commit,
                folder: '/srv/journal-cms'
            ],
            marker: 'journal_cms'
        )

        stage 'Approval'
        elifeGitMoveToBranch commit, 'approved'
    }
}
