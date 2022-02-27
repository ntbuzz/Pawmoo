<?php
/*
	Replace by setup.sh command
	ex.
	 # create (model)_def.csv into Condif/Setup folder)
		$ setup.sh schema (app) (model)		# CSV convert to (model)Schema into Config/Schema, LANG resource into Config/Proto/Lang
		$ setup.sh model  (app) (model)		# Schema convert to Model into Config/Proto/Models
		$ setup.sh table  (app) (model)		# execute SQL for TABLE and VIEW CREATE
		$ setup.sh data   (app) (model)		# inport Table Data from CSV in Config/CSV
*/
class {module}Model extends AppModel {

}
