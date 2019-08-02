#!/usr/bin/env ruby
#
# Script using chandler gem to update title/notes of release in GitHub
#

require 'rubygems'
require 'chandler'
require 'chandler/configuration'

github_repository = ENV['TRAVIS_REPO_SLUG']
tag = ENV['TRAVIS_TAG']
version = ENV['RELEASE_TITLE']
notes = ENV['RELEASE_NOTES']

github = Chandler::GitHub.new(
	:repository => github_repository,
	:config => Chandler::Configuration.new
)
github.create_or_update_release(
	:tag => tag,
	:title => version,
	:description => notes
)
