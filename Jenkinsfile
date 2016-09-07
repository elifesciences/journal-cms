elifePipeline {
    stage 'Checkout'
    checkout scm
    def commit = elifeGitRevision()

    stage 'Project tests'
    // there are no PHPUnit tests to produce an artifact
    // def testArtifact = "${env.BUILD_TAG}.junit.xml"
    builderDeployRevision 'journal-cms--ci', commit
    // only smoke tests will run inside this step:
    builderProjectTests 'journal-cms--ci', '/srv/journal-cms'
    // builderTestArtifact testArtifact, 'journal-cms--ci', '/srv/journal-cms/build/phpunit.xml'
    // elifeVerifyJunitXml testArtifact

    elifeMainlineOnly {
        stage 'End2end tests'
        elifeEnd2EndTest(
            {
                builderDeployRevision 'journal-cms--end2end', commit
            },
            'two'
        )

        stage 'Approval'
        elifeGitMoveToBranch commit, 'approved'
    }
}
