<?php

/*
*
* Copyright 2001, 2019 Thomas Belliard, Laurent Delineau, Edouard Hue, Eric Lebrun, Stephane Boireau
*
* This file is part of GEPI.
*
* GEPI is free software; you can redistribute it and/or modify
* it under the terms of the GNU General Public License as published by
* the Free Software Foundation; either version 2 of the License, or
* (at your option) any later version.
*
* GEPI is distributed in the hope that it will be useful,
* but WITHOUT ANY WARRANTY; without even the implied warranty of
* MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
* GNU General Public License for more details.
*
* You should have received a copy of the GNU General Public License
* along with GEPI; if not, write to the Free Software
* Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
*/

// Initialisation des feuilles de style après modification pour améliorer l'accessibilité
$accessibilite="y";
// Begin standart header
$niveau_arbo = 1;

// Initialisations files
require_once("../lib/initialisations.inc.php");
// Resume session
$resultat_session = $session_gepi->security_check();
if ($resultat_session == 'c') {
	header("Location: ../utilisateurs/mon_compte.php?change_mdp=yes");
	die();
} else if ($resultat_session == '0') {
	header("Location: ../logout.php?auto=1");
	die();
}

// SQL : INSERT INTO droits VALUES ( '/mod_discipline/visu_disc.php', 'F', 'F', 'F', 'F', 'V', 'V', 'F', 'F', 'Discipline: Accès élève/parent', '');
if (!checkAccess()) {
	header("Location: ../logout.php?auto=1");
	die();
}

if(mb_strtolower(mb_substr(getSettingValue('active_mod_discipline'),0,1))!='y') {
	$mess=rawurlencode("Vous tentez d accéder au module Discipline qui est désactivé !");
	tentative_intrusion(1, "Tentative d'accès au module Discipline qui est désactivé.");
	header("Location: ../accueil.php?msg=$mess");
	die();
}

//**************** EN-TETE *****************
$titre_page = "Discipline : Accès ".$_SESSION['statut'];
require_once("../lib/header.inc.php");
//**************** FIN EN-TETE *****************

if($_SESSION['statut']=='eleve') {
	if((getSettingAOui('visuEleDisc'))&&(getSettingAOui('visuEleDiscNature'))) {
		echo "<p style='color:red'>Vous n'êtes pas autorisé à accéder à cette page.</p>\n";
		tentative_intrusion(1, "Tentative d'accès au module Discipline sans y être autorisé.");
		require("../lib/footer.inc.php");
		die();
	}
}
elseif($_SESSION['statut']=='responsable') {
	if((getSettingAOui('visuRespDisc'))&&(getSettingAOui('visuRespDiscNature'))) {
		echo "<p style='color:red'>Vous n'êtes pas autorisé à accéder à cette page.</p>\n";
		tentative_intrusion(1, "Tentative d'accès au module Discipline sans y être autorisé.");
		require("../lib/footer.inc.php");
		die();
	}
}

echo "<p class='bold'><a href='../accueil.php'><img src='../images/icons/back.png' alt='Retour à l'accueil' class='back_link'/> Retour</a>";

if($_SESSION['statut']=='eleve') {
	$ele_login=$_SESSION['login'];
}
else {
	// Lien de choix de l'élève
	$ele_login=isset($_GET['ele_login']) ? $_GET['ele_login'] : NULL;

	$tab_ele_login=array();
	if(getSettingAOui('GepiMemesDroitsRespNonLegaux')) {
		$tab_enfants=get_enfants_from_resp_login($_SESSION['login'],'avec_classe', "yy");
	}
	else {
		$tab_enfants=get_enfants_from_resp_login($_SESSION['login'],'avec_classe');
	}
	if(!isset($tab_enfants)) {
		echo "<p style='color:red'>Vous n'avez semble-t-il aucun élève en responsabilité.</p>\n";
		tentative_intrusion(1, "Tentative d'accès au module Discipline sans élève en responsabilité.");
		require("../lib/footer.inc.php");
		die();
	}
	for($i=0;$i<count($tab_enfants);$i+=2) {
		//echo "\$tab_enfants[$i]=".$tab_enfants[$i]."<br />";
		$tab_ele_login[]=$tab_enfants[$i];
	}

	if((isset($ele_login))&&(!in_array($ele_login,$tab_ele_login))) {
		echo "<p style='color:red'>Tentative d'accès au module Discipline pour un élève dont vous n'êtes pas responsable.</p>\n";
		tentative_intrusion(1, "Tentative d'accès au module Discipline pour un élève dont il n'est pas responsable : $ele_login");
		unset($ele_login);
	}

	if(!isset($ele_login)) {
		if(count($tab_ele_login)==1) {
			$ele_login=$tab_ele_login[0];
		}
		else {
			echo "<p>Choisissez l'enfant dont vous souhaitez consulter les incidents&nbsp;:<br />\n";
			for($i=0;$i<count($tab_enfants);$i+=2) {
				echo "<a href='".$_SERVER['PHP_SELF']."?ele_login=".$tab_enfants[$i]."'>".$tab_enfants[$i+1]."</a><br />\n";
			}

			require("../lib/footer.inc.php");
			die();
		}
	}
	else {
		echo " | <a href='".$_SERVER['PHP_SELF']."'>Autre enfant</a>";
	}
}
echo "</p>\n";

require_once("../mod_discipline/sanctions_func_lib.php");

/*
$liste_incidents_eleve_jours=liste_incidents_eleve_jours($ele_login, 7, 0);
if($liste_incidents_eleve_jours!='') {
	//title=\"".ucfirst($mod_disc_terme_incident)."s des 7 derniers jours.\"
	echo "<div class='fieldset_opacite50' style='padding:0.3em; margin:0.5em;' ><p class='bold'>".ucfirst($mod_disc_terme_incident)."s des 7 derniers jours</p>".$liste_incidents_eleve_jours."</div>";
}
*/

$liste_sanctions_a_venir_eleve=liste_sanctions_a_venir_eleve($ele_login);
if($liste_sanctions_a_venir_eleve!='') {
	//title=\"".ucfirst($mod_disc_terme_sanction)."s dans les jours à venir.\"
	echo "<div class='fieldset_opacite50' style='padding:0.3em; margin:0.5em;' ><p class='bold'>".ucfirst($mod_disc_terme_sanction)."s dans les jours à venir</p>".$liste_sanctions_a_venir_eleve."</div>";
}

$mode="";
$date_debut="";
$date_fin="";
//echo "<p>Tableau des incidents</p>\n";

$tableau_des_avertissements_de_fin_de_periode_eleve_de_cet_eleve=tableau_des_avertissements_de_fin_de_periode_eleve($ele_login);
if($tableau_des_avertissements_de_fin_de_periode_eleve_de_cet_eleve!='') {
	echo "<div style='float:right; width:25em; margin-bottom:0.5em; margin-left:0.5em;'>".$tableau_des_avertissements_de_fin_de_periode_eleve_de_cet_eleve."</div>\n";
}

if((getSettingAOui('active_mod_disc_pointage'))&&
((($_SESSION['statut']=='eleve')&&(getSettingAOui('disc_pointage_acces_totaux_ele')))||(($_SESSION['statut']=='responsable')&&(getSettingAOui('disc_pointage_acces_totaux_resp'))))) {
	echo retourne_tab_html_pointages_disc($ele_login);
}

echo tab_mod_discipline($ele_login,$mode,$date_debut,$date_fin);

require("../lib/footer.inc.php");

?>
