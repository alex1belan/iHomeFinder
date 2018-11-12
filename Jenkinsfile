pipeline {
  agent any
  stages {
    stage('test step') {
      steps {
        build(job: 'test job', propagate: true)
      }
    }
  }
}