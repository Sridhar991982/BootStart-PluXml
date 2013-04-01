<?php

/**
 * Gestion des plugins
 *
 * @package PLX
 * @author	Stephane F
 * modification 30/03/2013 @author Jonathan Maris for © littleRabbitLabs
 **/

include(dirname(__FILE__).'/prepend.php');

# Control du token du formulaire
plxToken::validateFormToken($_POST);

# Control de l'accès à la page en fonction du profil de l'utilisateur connecté
$plxAdmin->checkProfil(PROFIL_ADMIN);

if(isset($_POST['submit'])) {

	$_POST['selection'] = $_POST['selection'][0] | $_POST['selection'][1];

	if($_POST['selection'] == 'delete') {
		if(!empty($_POST['action'])) {
			$error=false;
			foreach($_POST['action'] as $plugName => $activate) {
				if($plxAdmin->plxPlugins->deleteDir(realpath(PLX_PLUGINS.$plugName))) {
					unlink(PLX_ROOT.PLX_CONFIG_PATH.'plugins/'.$plugName.'.xml');
					unset($_POST['plugName'][$plugName]);
					unset($plxAdmin->plxPlugins->aPlugins[$plugName]);
				}
				else $error=true;
			}
			if(!$error)	$error=!$plxAdmin->plxPlugins->saveConfig($_POST);
			if($error) plxMsg::Error(L_PLUGINS_DELETE_ERROR);
			else plxMsg::Info(L_PLUGINS_DELETE_SUCCESSFUL);
			header('Location: parametres_plugins.php');
			exit;
		}
	}
	elseif($_POST['selection'] == 'activate' OR $_POST['selection'] == 'deactivate') {
		$plxAdmin->plxPlugins->saveConfig($_POST);
		header('Location: parametres_plugins.php');
		exit;
	}
}
elseif(isset($_POST['update'])) {
	$plxAdmin->plxPlugins->saveConfig($_POST);
	header('Location: parametres_plugins.php');
	exit;
}

# on récupère la liste des plugins dans le dossier plugins
$plxAdmin->plxPlugins->getList();

# On inclut le header
include(dirname(__FILE__).'/top.php');
?>

<div class="row-fluid">
  <div class="span12 widget">
    <div class="widget-title"><span class="icon"><i class="icon-wrench icon-grey icon-shadowed"></i></span>
      <h5><?php echo L_PLUGINS_TITLE ?></h5>
    </div>
    <div class="widget-content">
      <?php eval($plxAdmin->plxPlugins->callHook('AdminSettingsPluginsTop')) # Hook Plugins ?>
      <form class="form" action="parametres_plugins.php" method="post" id="form_plugins">
        <?php echo plxToken::getTokenPostMethod() ?>
        <div class="control-group">
          <div class="controls" style="margin-left:0; margin-top: 15px">
            <div class="input-append">
              <?php plxUtils::printSelect('selection[]', array(''=> L_FOR_SELECTION, 'activate'=> L_PLUGINS_ACTIVATE, 'deactivate'=> L_PLUGINS_DEACTIVATE,'-'=> '-----','delete'=> L_PLUGINS_DELETE),'', false,'',false); ?>
              <input class="btn submit" type="submit" name="submit" value="<?php echo L_OK ?>" />
            </div>
          </div>
        </div>
        <table class="table table-bordered table-hover">
          <thead>
            <tr>
              <th>&nbsp;</th>
              <th class="hidden-phone">&nbsp;</th>
              <th><a title="<?php echo L_PLUGINS_ALPHA_SORT ?>" href="parametres_plugins.php?sort"><?php echo L_MENU_CONFIG_PLUGINS ?></a></th>
              <th class="hidden-phone"><a href="parametres_plugins.php"><?php echo L_PLUGINS_LOADING_SORT ?></a></th>
              <th><?php echo L_PLUGINS_ACTION ?></th>
            </tr>
          </thead>
          <tbody>
            <?php
	$tmp = array();
	foreach($plxAdmin->plxPlugins->aPlugins as $plugName => $plugin) {
		if(isset($plugin['instance']))
			$tmp[] = strtolower($plugin['instance']->getInfo('title'));
		else
			unset($plxAdmin->plxPlugins->aPlugins[$plugName]);
	}
	if(sizeof($tmp)>0) {
		# Tri des plugins par titre
		if(isset($_GET['sort']) OR !is_file(path('XMLFILE_PLUGINS'))) {
			array_multisort($tmp, $plxAdmin->plxPlugins->aPlugins);
		}

		# Affichage des plugins
		$num=1;
		foreach($plxAdmin->plxPlugins->aPlugins as $plugName => $plugAttrs) {
			$plugin = $plugAttrs['instance'];

			# determination de l'icone à afficher
			if(is_file(PLX_PLUGINS.$plugName.'/icon.png'))
				$icon=PLX_PLUGINS.$plugName.'/icon.png';
			elseif(is_file(PLX_PLUGINS.$plugName.'/icon.jpg'))
				$icon=PLX_PLUGINS.$plugName.'/icon.jpg';
			elseif(is_file(PLX_PLUGINS.$plugName.'/icon.gif'))
				$icon=PLX_PLUGINS.$plugName.'/icon.gif';
			else
			$icon=PLX_CORE.'admin/theme/images/icon_plugin.png';

			echo '<tr class="plugins-'.$plugAttrs['activate'].' top">';

			echo '<td>';
			echo '<input type="hidden" name="plugName['.$plugName.']" value="'.$plugAttrs['activate'].'" />';
			echo '<input type="hidden" name="plugTitle['.$plugName.']" value="'.plxUtils::strCheck($plugin->getInfo('title')).'" />';
			echo '<input type="checkbox" name="action['.$plugName.']" />';
			echo '</td>';

			echo '<td class="hidden-phone"><img src="'.$icon.'" alt="" /></td>';

			# si pour le plugin un fichier config.php existe on créer le lien pour accèder à l'écran
			# de configuration du plugin
			echo '<td><div class="alert alert-info"><ul class="unstyled">';
			echo '<li><strong>'.plxUtils::strCheck($plugin->getInfo('title')).'</strong></li>';
			echo '<li>'.L_PLUGINS_VERSION.': <strong>'.plxUtils::strCheck($plugin->getInfo('version')).'</strong></li>';
			if($plugin->getInfo('date')!='')
			echo '<li>'.plxUtils::strCheck($plugin->getInfo('date')).'</li>';
			
			echo '<li>'.plxUtils::strCheck($plugin->getInfo('description')).'</li>';
			echo '<li>'.L_PLUGINS_AUTHOR.': '.plxUtils::strCheck($plugin->getInfo('author')).'</li>';
			if($plugin->getInfo('site')!='') echo '<li><a href="'.plxUtils::strCheck($plugin->getInfo('site')).'">'.plxUtils::strCheck($plugin->getInfo('site')).'</a></li>';
			echo '</div></td>';

			echo '<td class="hidden-phone">';
			echo '<input class="span12" size="2" maxlength="3" type="text" name="plugOrdre['.$plugName.']" value="'.$num++.'" />';
			echo '</td>';

			# affichage des liens pour acceder à l'aide et à la configuration du plugin
			echo '<td class="right">';
			if(is_file(PLX_PLUGINS.$plugName.'/lang/'.$plxAdmin->aConf['default_lang'].'-help.php'))
			echo '<a class="btn btn-mini" rel="tooltip" data-toggle="tooltip" data-placement="top" data-original-title="'.L_PLUGINS_HELP_TITLE.'" href="parametres_pluginhelp.php?p='.urlencode($plugName).'">'.L_PLUGINS_HELP.'</a>';
			# affichage du lien pour configurer le plugin
			if(is_file(PLX_PLUGINS.$plugName.'/config.php'))
			echo '<a class="btn btn-mini" rel="tooltip" data-toggle="tooltip" data-placement="top" data-original-title="'.L_PLUGINS_CONFIG_TITLE.'" href="parametres_plugin.php?p='.urlencode($plugName).'">'.L_PLUGINS_CONFIG.'</a>';
			//if(trim($plugin->getInfo('requirements'))!='')
			//echo L_PLUGINS_REQUIREMENTS.' : '.plxUtils::strCheck($plugin->getInfo('requirements'));
			echo '</td>';
			echo '</tr>';
		}
	}
	else
		echo '<tr><td colspan="5" class="center">'.L_NO_PLUGIN.'</td></tr>';

?>
          </tbody>
        </table>
        <div class="control-group pull-right">
          <div class="controls" style="margin-left:0;">
            <input class="btn btn-responsive update" type="submit" name="update" value="<?php echo L_PLUGINS_APPLY_BUTTON ?>" />
          </div>
        </div>
        <div class="control-group">
          <div class="controls" style="margin-left:0;">
            <div class="input-append">
              <?php plxUtils::printSelect('selection[]', array(''=> L_FOR_SELECTION, 'activate'=> L_PLUGINS_ACTIVATE, 'deactivate'=> L_PLUGINS_DEACTIVATE,'-'=> '-----','delete'=> L_PLUGINS_DELETE),'', false,'',false); ?>
              <input class="btn submit" type="submit" name="submit" value="<?php echo L_OK ?>" />
            </div>
          </div>
        </div>
      </form>
    </div>
  </div>
</div>
<?php
# Hook Plugins
eval($plxAdmin->plxPlugins->callHook('AdminSettingsPluginsFoot'));
# On inclut le footer
include(dirname(__FILE__).'/foot.php');
?>
