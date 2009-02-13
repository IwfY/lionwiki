<?php
/** File upload plugin for Wikiss, adjusted and slightly improved for LionWiki
 * access with : ?action=upload
 */

class Upload
{
  /** Check/corect path validity
   * remove .., ^/, //, /$ and decode path
   * input: path
   * return corrected path
   */
	
	var $datadir;
	
	var $blacklist = array(".php", ".phtml", ".php3", ".php4", ".js", ".shtml", ".pl" ,".py", ".asp", ".jsp", ".sh", ".cgi"); // forbidden file extensions
	
	// permissions of uploaded files and directories. Leading zeroes are necessary!
	var $chmod_dir = 0755;
	var $chmod_file = 0644;

  function Upload()
  {
  	global $BASE_DIR, $self;
  
  	$this->datadir = $BASE_DIR . "data/";
  
    $this->localize();
    
    $this->desc = array(
			array("<a href=\"$self?action=upload\" rel=\"nofollow\">Upload plugin</a>", "provides uploading files to the server.")
		);
  }

  function cleanInput($input)
  {
    return trim(preg_replace('/(\.\.|^\/|\/\/)/', '', urldecode($input)), '/');
  }

  function action($action)
  {
    global $CON, $TITLE, $editable, $T_PASSWORD, $TE_WRONG_PASSWORD, $error;

    $curdir = "";
    
    if($action == "upload") {
      $CON = "";
      
      $TITLE = $this->TP_FILE_UPLOAD;

      if(is_dir($this->datadir)) {
        $curdir = $this->cleanInput($_REQUEST['curdir']);

        if(!preg_match('/^' . preg_quote(trim($this->datadir, "/"), "/") . "/", $curdir)) // curdir must be data dir subdirectory
          $curdir = trim($this->datadir, '/');

        if(authentified()) {
          if(!empty($_POST['dir2create']))
            @mkdir($curdir . '/' . $this->cleanInput($_POST['dir2create']), $this->chmod_dir);
          elseif(!empty($_FILES['file']['tmp_name'])) { // anything to upload?
            if(is_uploaded_file($_FILES['file']['tmp_name'])) {
							foreach($this->blacklist as $file) // executable files not allowed
								if(preg_match("/" . preg_quote($file) . "$/i", $_FILES['file']['name'])) {  
									$error .= $this->TP_NO_EXECUTABLE;

									break;
							}
							
							if(!strcasecmp($_FILES['file']['name'], ".htaccess"))
								$error .= $this->TP_NO_EXECUTABLE;

							if(empty($error)) {
								@move_uploaded_file($_FILES['file']['tmp_name'], $curdir . '/' . $_FILES['file']['name']);
								@chmod($curdir . '/' . $_FILES['file']['name'], $this->chmod_file);
							}
            }
          } elseif($_FILES['file']['error'] != UPLOAD_ERR_OK)
            $error = "$this->TP_ERROR_UPLOADING ($_FILES[file][error])";

          if(isset($_GET['del'])) { // delete file/directory
            $file = $this->cleanInput($_GET['del']);

						$ret = is_dir($file) ? @rmdir($file) : @unlink($file);
          }
        } elseif($_SERVER['REQUEST_METHOD'] == 'POST')
          $error = $TE_WRONG_PASSWORD;

        // list of files
        if($opening_dir = @opendir($curdir)) {
          $CON .= '<h2>' . $this->TP_DIRECTORY . " " . $curdir . '</h2><table id="fileTable" style="min-width : 600px;"><col span="2" style="color : red;" /><col /><col style="text-align : right;" /><col style="text-align : center;" /><tr><th>' . $this->TP_FILE_NAME . '</th><th>' . $this->TP_FILE_TYPE . '</th><th>' . $this->TP_FILE_SIZE . '</th><th>' . $this->TP_DELETE . '</th></tr>';

          $files = array();

          while($filename = @readdir($opening_dir)) // do not add link to parent of data_dir
            if(strcasecmp($filename, '.htaccess') && $filename != '.' && ($filename != '..' || $curdir != trim($this->datadir, '/')))
              $files[] = array($filename, is_dir($curdir . "/" . $filename));

          
          function cmp_files($a, $b) // sort directories first, then files.
          {
            if($a[1] == $b[1])
              return strcmp($a[0], $b[0]) < 0 ? -1 : 1;
            else
              return $b[1];
          }

					if($files)
            usort($files, "cmp_files");

          foreach($files as $file) {
            if($file[0] == '..')
              $path = substr($curdir, 0, strrpos($curdir, '/'));
            else
              $path = $curdir . '/' . $file[0];

            $CON .= "<tr>";

            if(is_dir($path))
              $CON = $CON . '<td><a href="'.$self.'?action=' . $action . '&curdir=' . urlencode($path) . '">[' . $file[0] . ']</a></td><td>' . $this->TP_DIRECTORY . '</td><td>-</td>';
            else
              $CON = $CON . '<td><a href="' . $path . '">' . $file[0] . '</a></td><td>' . $this->TP_FILE . '</td><td>'.@number_format(@filesize($path), 0, ".", " ") . ' B</td>';

            if((authentified()) && ($file[0] != '..'))
              $CON .= '<td><a title="delete" href="'.$self.'?action=upload&del=' . urlencode($path) . "&curdir=" . urlencode($curdir) . '">&times;</a></td>';
            else
            	$CON .= "<td>&nbsp;</td>";

            $CON .= "</tr>\n";
          }

          $CON .= "</table>";
        }

        $CON .= '
<h2>Upload</h2>
        
<div id="upload-form">
  <form method="post" action="'.$self.'?action=' . $action . '" enctype= "multipart/form-data">
  <input type="hidden" name="curdir" value="' . $curdir . '" />
';
        $CON .= "$this->TP_FILE: <input type=\"file\" name=\"file\" />\n";
        
        if(!authentified())
          $CON .= "$T_PASSWORD: <input type=\"password\" name=\"sc\" />\n";
        
        $CON .= "<input type=\"submit\" value=\"$this->TP_UPLOAD\" />";
        
        $CON .= "</form>";
        
        $CON .= "<p><em>$this->TP_MAXIMUM_SIZE_IS " . ini_get('upload_max_filesize') . "</em></p>";
        
        $CON .= '
	<form method="post" action="'.$self.'?action=' . $action . '" enctype= "multipart/form-data">
  <input type="hidden" name="curdir" value="' . $curdir . '" />';
        
        $CON .= "$this->TP_CREATE_DIRECTORY: <input type=\"text\" name=\"dir2create\" />\n";

				if(!authentified())
          $CON .= "$T_PASSWORD: <input type=\"password\" name=\"sc\" />\n";

				$CON .= "<input type=\"submit\" value=\"$this->TP_CREATE\" />";
        
        $CON .= "</form></div>";

      } else
        $CON = "<div class=\"error\">$this->TP_NO_DATA_DIR ($this->datadir).</div>";

      return true;
    }
    
    return false;
  }
  
  function template()
  {				
  	global $html;
	  
		$html = template_replace("plugin:UPLOAD", "<a href=\"$self?action=upload\" rel=\"nofollow\">Upload</a>", $html);
	}
  
  // Localization strings

  var $cs_strings = array(
		array("TP_FILE_UPLOAD", "Upload souborů"),
		array("TP_FILE_NAME", "Jméno souboru"),
		array("TP_FILE_TYPE", "Typ souboru"),
		array("TP_FILE_SIZE", "Velikost"),
		array("TP_DELETE", "Smazat"),
		array("TP_ERROR_UPLOADING", "Nastala chyba při uploadu souboru"),
		array("TP_FILE", "Soubor"),
		array("TP_DIRECTORY", "Adresář"),
		array("TP_CREATE_DIRECTORY", "Vytvořit adresář"),
		array("TP_CREATE", "Vytvořit"),
		array("TP_UPLOAD", "Nahrát"),
		array("TP_MAXIMUM_SIZE_IS", "Maximální velikost souboru je"),
		array("TP_NO_DATA_DIR", "Adresář pro data neexistuje"),
		array("TP_NO_EXECUTABLE", "Uploadování spustitelných souborů je zakázané.")
	);


  var $en_strings = array(
		array("TP_FILE_UPLOAD", "File upload"),
		array("TP_FILE_NAME", "File name"),
		array("TP_FILE_TYPE", "Type"),
		array("TP_FILE_SIZE", "Size"),
		array("TP_DELETE", "Delete"),
		array("TP_ERROR_UPLOADING", "Error ocurred during uploading file"),
		array("TP_FILE", "File"),
		array("TP_DIRECTORY", "Directory"),
		array("TP_CREATE_DIRECTORY", "Create directory"),
		array("TP_CREATE", "Create"),
		array("TP_UPLOAD", "Upload"),
		array("TP_MAXIMUM_SIZE_IS", "Maximum size of uploaded file is"),
		array("TP_NO_DATA_DIR", "Data directory doesn't exist"),
		array("TP_NO_EXECUTABLE", "Upload of executable files is not permitted.")
	);
	
	var $fr_strings = array(
		array("TP_FILE_UPLOAD", "File upload"),
		array("TP_FILE_NAME", "Nom du fichier/dossier"),
		array("TP_FILE_TYPE", "Type"),
		array("TP_FILE_SIZE", "Taille"),
		array("TP_DELETE", "Supprimer"),
		array("TP_ERROR_UPLOADING", "Une erreur a empêché le téléversement du fichier"),
		array("TP_FILE", "Fichier"),
		array("TP_DIRECTORY", "Dossier"),
		array("TP_CREATE_DIRECTORY", "Créer un dossier"),
		array("TP_UPLOAD", "Upload"),
		array("TP_MAXIMUM_SIZE_IS", "Si vous avez besoin d'un dossier dans lequel vous voulez déposer un fichier, vous devez d'abord créer le dossier dans un premier temps puis téléverser le fichier.<br />La taille maximum à utiliser pour un fichier transféré est de"),
		array("TP_NO_DATA_DIR", "Cet élément n'existe pas"),
		array("TP_NO_EXECUTABLE", "Le téléversement de fichiers éxécutables n'est pas autorisé.")
	);

  function localize()
  {
    global $LANG;

    foreach($this->en_strings as $str)
      $this->$str[0] = $str[1];

    if($LANG != "en" && isset($this->{$LANG . "_strings"}))
      foreach($this->{$LANG . "_strings"} as $str)
        $this->$str[0] = $str[1];
  }
}
?>
