
require 'erb'
require 'pp'
require 'json'

# optiional parameters and their defaults
template_name = ENV['TEMPLATE_NAME'] || "new_template"
vagrant_box = ENV['VAGRANT_BOX'] || "centos64"
output_dir = ENV['OUTPUT_DIR'] || "templates/build"

cookbook_name = template_name
template_srcdir = "templates/src"
template_outdir = "#{output_dir}/#{template_name}"

directory template_outdir

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
directory berks_cookbooks_dir
file berksfile_path => [template_outdir,berks_cookbooks_dir] do
	params = {
		:template_name => template_name,
		:cookbook_name => cookbook_name
	}
	erb_sub("#{template_srcdir}/Berksfile.erb",berksfile_path,params)
end

# create cookbook
task :cookbook_init do
	Dir.chdir(template_outdir) do
		if !::File.exists?(cookbook_name) then
			sh "knife cookbook create #{cookbook_name} -o ."
		end
	end
end

# configure role
role_dir = "#{template_outdir}/roles"
role_path = "#{role_dir}/vagrant.json"
directory role_dir

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
	File.open role_path, 'w' do |f|
		f.write JSON.pretty_generate(role_params)
	end
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
directory cache_dir

# set up task and defaults
task :init => [vagrantfile_path, berksfile_path, :cookbook_init, role_path, bootstrap_script, cache_dir]
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
