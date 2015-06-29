#### PHP Grid Manager

Library for managing batch processing jobs in an elastic server environment.

### Template Generator

A rakefile and templates are provided for generating a chef cookbook and 
application files to bootstrap a new application utilizing the GridManager
library.

To generate a template, run `rake`. By default it will be placed in `templates/build/new_template`.

Optional environment variables to tweak the behavior of template generation:
<table>
	<tr>
		<th>Variable</th><th>Default Value</th>
	</tr>
	<tr>
		<td>TEMPLATE_NAME</td><td>`new_template`</td>
	</tr>
	<tr>
		<td>VAGRANT_BOX</td><td>`centos64`</td>
	</tr>
	<tr>
		<td>VAGRANT_BOX_URL</td><td>`http://raven-opensource.s3.amazonaws.com/centos64.box`</td>
	</tr>
	<tr>
		<td>OUTPUT_DIR</td><td>`templates/build`</td>
	</tr>
	<tr>
		<td>AUTHOR_NAME</td><td>`Admin`</td>
	</tr>
	<tr>
		<td>AUTHOR_EMAIL</td><td>`admin@raventools.com`</td>
	</tr>
</table>

For example, to create a template called `pic_processor`, run:
`TEMPLATE_NAME=pic_processor rake`
