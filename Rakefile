
require 'erb'
require 'pp'
require 'json'

# optiional parameters and their defaults
template_name = ENV['TEMPLATE_NAME'] || "new_template"
vagrant_box = ENV['VAGRANT_BOX'] || "centos64"
vagrant_box_url = ENV['VAGRANT_BOX_URL'] || "http://raven-opensource.s3.amazonaws.com/centos64.box"
output_dir = ENV['OUTPUT_DIR'] || "templates/build"
author_name = ENV["AUTHOR_NAME"] || "Admin"
author_email = ENV["AUTHOR_EMAIL"] || "admin@raventools.com"

# important variables
cookbook_name = template_name
template_srcdir = "templates/src"
template_outdir = "#{output_dir}/#{template_name}"

directory template_outdir

def classify(str)
	str.split('_').collect(&:capitalize).join
end

# create Vagrantfile
vagrantfile_path = "#{template_outdir}/Vagrantfile"
file vagrantfile_path => template_outdir do
	# template input parameters
	params = {
		:template_name => template_name,
		:vagrant_box => vagrant_box,
		:vagrant_box_url => vagrant_box_url
	}
	erb_sub("#{template_srcdir}/Vagrantfile.erb",vagrantfile_path,params)
end

# create Berksfile
berksfile_path = "#{template_outdir}/Berksfile"
berks_cookbooks_dir = "#{template_outdir}/berks-cookbooks"
directory berks_cookbooks_dir => template_outdir
file berksfile_path => [berks_cookbooks_dir] do
	params = {
		:template_name => template_name,
		:cookbook_name => cookbook_name
	}
	erb_sub("#{template_srcdir}/Berksfile.erb",berksfile_path,params)
end

# create cookbook
cookbook_path = "#{template_outdir}/#{cookbook_name}"
recipe_path = "#{cookbook_path}/recipes"
default_recipe_path = "#{recipe_path}/default.rb"
file default_recipe_path => template_outdir do
	params = {
		:program_name => template_name,
		:cookbook_name => template_name
	}
	erb_sub("#{template_srcdir}/recipes_default.rb.erb",default_recipe_path,params)
end

attribute_path = "#{cookbook_path}/attributes"
default_attribute_path = "#{attribute_path}/default.rb"
file default_attribute_path => template_outdir do
	params = {
		:program_name => template_name,
		:cookbook_name => template_name
	}
	erb_sub("#{template_srcdir}/attributes_default.rb.erb",default_attribute_path,params)
end

metadata_path = "#{cookbook_path}/metadata.rb"
file metadata_path => template_outdir do
	params = {
		:cookbook_name => template_name
	}
	erb_sub("#{template_srcdir}/metadata.rb.erb",metadata_path,params)
end

task :create_cookbook => template_outdir do
	Dir.chdir(template_outdir) do
		if !::File.exists?(cookbook_name) then
			sh "knife cookbook create #{cookbook_name} -o ."
			sh "rm #{cookbook_name}/recipes/default.rb"
			sh "rm #{cookbook_name}/metadata.rb"
		end
	end
end

task :cookbook_init => [:create_cookbook, default_recipe_path, default_attribute_path, metadata_path]

# configure role
role_dir = "#{template_outdir}/roles"
role_path = "#{role_dir}/vagrant.json"
directory role_dir => template_outdir

file role_path => role_dir do
	role_params = {
		"name" => "vagrant",
		"description" => "vagrant development role",
		"json_class" => "Chef::Role",
		"default_attributes" => { },
		"override_attributes" => { },
		"chef_type" => "role",
		"run_list" => [
			"raven-supervisor",
			cookbook_name
		],
		"env_run_lists" => { }
	}
	json_file(role_path, role_params)
end

# configure shell provisioner
cache_dir = "#{template_outdir}/cache"
directory cache_dir
scripts_dir = "#{template_outdir}/scripts"
directory scripts_dir
bootstrap_script = "#{scripts_dir}/vagrant_bootstrap.sh"
file bootstrap_script => [scripts_dir] do
	params = { }
	erb_sub("#{template_srcdir}/vagrant_bootstrap.sh.erb",bootstrap_script,params)
end

# set up cache directory
cache_dir = "#{template_outdir}/cache"
directory cache_dir => template_outdir

# set up composer.json
php_namespace = classify(template_name)
composer_path = "#{template_outdir}/composer.json"
file composer_path => template_outdir do
	params = {
		"name" => "#{template_name}",
		"description" => "Application implementing GridManager",
		"authors" => [
			{
				"name" => author_name,
				"email" => author_email
			}
		],
		"autoload" => {
			"psr-0" => {
				php_namespace => "includes"
			}
		},
		"repositories" => [
			{
				"type" => "vcs",
				"url" => "https://github.com/RavenTools/GridManager.git"
			}
		],
		"require" => {
			"RavenTools/GridManager" => current_version
		}
	}
	json_file(composer_path,params)
end

# set up php application
php_include_dir = "#{template_outdir}/includes"
php_bootstrap_src = "#{template_srcdir}/bootstrap.php.erb"
php_bootstrap_path = "#{php_include_dir}/bootstrap.php"
directory php_include_dir => template_outdir
file php_bootstrap_path => php_include_dir do
	params = {
		:template_name => template_name,
		:namespace => classify(template_name)
	}
	erb_sub(php_bootstrap_src, php_bootstrap_path, params)
end

php_cli_src = "#{template_srcdir}/cli.php.erb"
php_cli_path = "#{template_outdir}/cli.php"
file php_cli_path => template_outdir do
	params = {
		:template_name => template_name,
		:namespace => classify(template_name)
	}
	erb_sub(php_cli_src, php_cli_path, params)
end

php_config_src = "#{template_srcdir}/Config.php.erb"
directory php_include_dir => template_outdir
php_namespace_dir = "#{php_include_dir}/#{php_namespace}"
directory php_namespace_dir => php_include_dir
php_config_path = "#{php_namespace_dir}/Config.php"

file php_config_path => php_namespace_dir do
	params = {
		:template_name => template_name,
		:namespace => classify(template_name)
	}
	erb_sub(php_config_src, php_config_path, params)
end

input_class_src = "#{template_srcdir}/Input.php.erb"
input_class_path = "#{php_namespace_dir}/Input.php"
file input_class_path => php_namespace_dir do
	params = {
		:template_name => template_name,
		:namespace => classify(template_name)
	}
	erb_sub(input_class_src, input_class_path, params)
end

output_class_src = "#{template_srcdir}/Output.php.erb"
output_class_path = "#{php_namespace_dir}/Output.php"
file output_class_path => php_namespace_dir do
	params = {
		:template_name => template_name,
		:namespace => classify(template_name)
	}
	erb_sub(output_class_src, output_class_path, params)
end

worker_class_src = "#{template_srcdir}/Worker.php.erb"
worker_class_path = "#{php_namespace_dir}/Worker.php"
file worker_class_path => php_namespace_dir do
	params = {
		:template_name => template_name,
		:namespace => classify(template_name)
	}
	erb_sub(worker_class_src, worker_class_path, params)
end

singleton_class_src = "#{template_srcdir}/Singleton.php.erb"
singleton_class_path = "#{php_namespace_dir}/Singleton.php"
file singleton_class_path => php_namespace_dir do
	params = {
		:template_name => template_name,
		:namespace => classify(template_name)
	}
	erb_sub(singleton_class_src, singleton_class_path, params)
end

log_class_src = "#{template_srcdir}/Log.php.erb"
log_class_path = "#{php_namespace_dir}/Log.php"
file log_class_path => php_namespace_dir do
	params = {
		:namespace => classify(template_name)
	}
	erb_sub(log_class_src, log_class_path, params)
end

phpunit_dir = "#{template_outdir}/tests"
directory phpunit_dir
phpunit_bootstrap_src = "#{template_srcdir}/phpunit_bootstrap.php.erb"
phpunit_bootstrap_path = "#{phpunit_dir}/bootstrap.php"
file phpunit_bootstrap_path => phpunit_dir do
	params = {
		:namespace => classify(template_name)
	}
	erb_sub(phpunit_bootstrap_src, phpunit_bootstrap_path, params)
end

phpunit_xml_src = "#{template_srcdir}/phpunit.xml.erb"
phpunit_xml_path = "#{template_outdir}/phpunit.xml"
file phpunit_xml_path => phpunit_dir do
	params = {
		:namespace => classify(template_name)
	}
	erb_sub(phpunit_xml_src, phpunit_xml_path, params)
end

config_json_path = "#{template_outdir}/config.json"
file config_json_path => template_outdir do
	config_json_params = {
		:timezone => "US/Eastern"
	}
	json_file(config_json_path, config_json_params)
end

task :init_php_app => [
	composer_path,
	php_bootstrap_path,
	php_cli_path,
	php_config_path,
	input_class_path,
	output_class_path,
	worker_class_path,
	singleton_class_path,
	log_class_path,
	phpunit_bootstrap_path,
	phpunit_xml_path,
	config_json_path
	]

# = other files
# == .gitignore 
gitignore_path = "#{template_outdir}/.gitignore"
file gitignore_path => template_outdir do
	contents = <<-EOH
cache
vendor
*.swp
*.swo
overrides.json
.vagrant
berks-cookbooks
EOH
	put_file(gitignore_path,contents)
end

task :other_files => [
	gitignore_path
]

# set up task and defaults
task :init => [
	vagrantfile_path, 
	berksfile_path, 
	:cookbook_init, 
	role_path, 
	bootstrap_script, 
	cache_dir, 
	:init_php_app,
	:other_files
]
task :default => [:init]

# helper for processing erb templates
def erb_sub(src, dst, params)
	# initialize erb template and substitute parameters
	out_str = ERB.new(File.read(src)).result binding
	# write resulting file
	File.open(dst,'w') do |f|
		f.write out_str
	end
end

def json_file(path, contents)
	File.open(path,'w') do |f|
		f.write JSON.pretty_generate(contents)
	end
end

def put_file(path, contents)
	File.open(path,'w') do |f|
		f.write contents
	end
end

def current_version
	%x(git tag -l | tail -n1).strip
end
