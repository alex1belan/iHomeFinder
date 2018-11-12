pipeline {
  agent any
  stages {
    stage('test step') {
      steps {
        sh '''<?php
namespace iHomefinder\\Api;
interface Authentication {
	
	public function getUsername();
	
	public function getPassword();
	
}'''
      }
    }
  }
}