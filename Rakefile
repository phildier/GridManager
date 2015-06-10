
require 'erb'
require 'pp'
require 'json'

# optiional parameters and their defaults
template_name = ENV['TEMPLATE_NAME'] || "new_template"
vagrant_box = ENV['VAGRANT_BOX'] || "centos64"
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
		:vagrant_box => vagrant_box
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
task :cookbook_init => template_outdir do
	Dir.chdir(template_outdir) do
		if !::File.exists?(cookbook_name) then
			sh "knife cookbook create #{cookbook_name} -o ."
		end
	end
end

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
	json_file(role_path, role_params);
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

task :init_php_app => [
	composer_path,
	php_bootstrap_path,
	php_cli_path,
	php_config_path,
	input_class_path,
	output_class_path,
	worker_class_path,
	singleton_class_path
	]

# set up task and defaults
task :init => [
	vagrantfile_path, 
	berksfile_path, 
	:cookbook_init, 
	role_path, 
	bootstrap_script, 
	cache_dir, 
	:init_php_app
]
task :default => [:init]

# helper for processing erb templates
def erb_sub(src, dst, params)
	# initialize erb template and substitute parameters
	out_str = ERB.new(File.read(src)).result binding
	# write resulting file
	File.open(dst,'w') do |f|
		f.write(out_str)
	end
end

def json_file(path, contents)
	File.open(path,'w') do |f|
		f.write JSON.pretty_generate(contents)
	end
end

def current_version
	%x(git tag -l | tail -n1).strip
end
