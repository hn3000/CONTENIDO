<?php
/*****************************************
* File      :   $RCSfile: include.workflow_tasks.php,v $
* Project   :   Contenido Workflow
* Descr     :   Workflow task overview mask
*
* Author    :   $Author: timo.hummel $
*               
* Created   :   30.07.2003
* Modified  :   $Date: 2003/08/14 13:07:32 $
*
* � four for business AG, www.4fb.de
*
* $Id: include.workflow_tasks.php,v 1.4 2003/08/14 13:07:32 timo.hummel Exp $
******************************************/
include_once ($cfg["path"]["classes"] . 'class.ui.php');
include_once ($cfg["path"]['contenido'] . $cfg["path"]["plugins"] . "workflow/classes/class.workflow.php"); 
include_once ($cfg["path"]['contenido'] . $cfg["path"]["plugins"] . "workflow/includes/functions.workflow.php");

global $sess;
$sSession = $sess->id;

$wfa = new WorkflowArtAllocations;
$wfu = new WorkflowUserSequences;
$users = new User;
$db2 = new DB_Contenido;

ob_start();

if ($usershow == "")
{
	$usershow = $auth->auth["uid"];
}

if (!$perm->have_perm_area_action($area, "workflow_task_user_select"))
{
	$usershow = $auth->auth["uid"];
}

if ($action == "workflow_do_action")
{
    $selectedAction = "wfselect".$modidartlang;       
    doWorkflowAction($modidartlang, $GLOBALS[$selectedAction]);
}

$wfa->select();

while ($wfaitem = $wfa->next())
{
	$wfaid = $wfaitem->get("idartallocation");
	$usersequence[$wfaid] = $wfaitem->get("idusersequence");
	$lastusersequence[$wfaid] = $wfaitem->get("lastusersequence");
	$article[$wfaid] = $wfaitem->get("idartlang");
} 


if (is_array($usersequence))
{
foreach ($usersequence as $key => $value)
{
	$wfu->select("idusersequence = '$value'");
	if ($obj = $wfu->next())
	{
		$userids[$key] = $obj->get("iduser");
	}
}
}

if (is_array($userids))
{
foreach ($userids as $key=>$value)
{
    $isCurrent[$key] = false;
    
    if ($usershow == $value)
    {
    	$isCurrent[$key] = true;
    }
    
    if ($users->loadUserByUserID($value) == false)
    {
    	/* Yes, it's a group. Let's try to load the group members! */
    	$sql = "SELECT user_id FROM "
    			.$cfg["tab"]["groupmembers"]."
                WHERE group_id = '".$value."'";
        $db2->query($sql);
   
        while ($db2->next_record())
        {
        	if ($db2->f("user_id") == $usershow)
        	{
        		$isCurrent[$key] = true;
        	}
        }
    } else {
    	if ($value == $usershow)
    	{
    		$isCurrent[$key] = true;
    	}
    }
    
    if ($lastusersequence[$key] == $usersequence[$key])
    {
    	$isCurrent[$key] = false;
    }
}
}

$tpl->reset();
$tpl->setEncoding('iso-8859-1');
$tpl->set('s', 'SESSID', $sSession);
$tpl->set('s', 'SESSNAME', $sess->name);
$iIDCat = 0;
$iIDTpl = 0;

if ($perm->have_perm_area_action($area, "workflow_task_user_select"))
{
    $form = new UI_Form("showusers", $sess->url("main.php?area=$area&frame=$frame"));
    $form->setVar("area",$area);
    $form->setEvent("submit", "setUsershow();");
    $form->setVar("frame", $frame);
    $form->setVar("action", "workflow_task_user_select");
    $form->add("select",i18n("Show users").": ".getUsers("show",$usershow));
    $form->add("button", '<input style="vertical-align:middle;" type="image" src="'.$cfg["path"]["htmlpath"].$cfg["path"]["images"]."submit.gif".'">');
    
    $tpl->set('s', 'USERSELECT', $form->render(true));
} else {
    $tpl->set('s', 'USERSELECT', '');
}

$pageTitle = i18n('Search results').' - '.i18n('Workflow tasks', 'workflow');
$tpl->set('s', 'PAGE_TITLE', $pageTitle);

$tpl->set('s', 'TH_START', i18n("Article"));
$tpl->set('s', 'TH_TEMPLATE', i18n("Template"));
$tpl->set('s', 'TH_ACTIONS', i18n("Actions"));
$tpl->set('s', 'TH_TITLE', i18n("Title"));
$tpl->set('s', 'TH_CHANGED', i18n("Changed"));
$tpl->set('s', 'TH_PUBLISHED', i18n("Published"));
$tpl->set('s', 'TH_WORKFLOW_STEP', i18n("Workflow Step", 'workflow'));
$tpl->set('s', 'TH_WORKFLOW_ACTION', i18n("Workflow Action", 'workflow'));
$tpl->set('s', 'TH_WORKFLOW_EDITOR', i18n("Workflow Editor"));
$tpl->set('s', 'TH_LAST_STATUS', i18n("Last status", 'workflow'));

$currentUserSequence = new WorkflowUserSequence;
    		
if (is_array($isCurrent))
{

foreach ($isCurrent as $key => $value)
{
	if ($value == true)
	{
		$idartlang = $article[$key];
    	$sql = "SELECT B.idcat AS idcat, A.title AS title, A.created AS created, A.lastmodified AS changed, 
                       A.idart as idart, E.name as tpl_name, A.idartlang as idartlang, F.idcatlang as idcatlang,
                       B.idcatart as idcatart, A.idlang as art_lang, F.startidartlang as startidartlang
    			FROM (".$cfg["tab"]["art_lang"]." AS A,
                     ".$cfg["tab"]["cat_art"]." AS B,
 					 ".$cfg["tab"]["art"]." AS C)
                      LEFT JOIN ".$cfg['tab']['tpl_conf']." as D ON A.idtplcfg = D.idtplcfg
                      LEFT JOIN ".$cfg['tab']['tpl']." as E ON D.idtpl = E.`idtpl`
                      LEFT JOIN ".$cfg['tab']['cat_lang']." as F ON B.idcat = F.`idcat`
					 WHERE A.idartlang = '$idartlang' AND
						   A.idart = B.idart AND
						   A.idart = C.idart AND
						   A.idlang = '$lang' AND
 						   C.idclient = '$client';";
    	$db->query($sql);

    	if ($db->next_record())
    	{
            global $area;
            //$area = "con";
    		$idcat = $db->f("idcat");
            $idart = $db->f("idart");       

    		 # create javascript multilink
        	$tmp_mstr = '<a href="javascript://" onclick="javascript:conMultiLink(\'%s\', \'%s\', \'%s\', \'%s\')"  title="idart: '.$db->f('idart').' idcatart: '.$db->f('idcatart').'" alt="idart: '.$db->f('idart').' idcatart: '.$db->f('idcatart').'">%s</a>';

        	$mstr = sprintf($tmp_mstr, 'right_top',
                                   $sess->url("main.php?area=con&frame=3&idcat=$idcat&idtpl=$idtpl"),
                                   'right_bottom',
                                   $sess->url("main.php?area=con_editart&action=con_edit&frame=4&idcat=$idcat&idtpl=$idtpl&idart=$idart"),
                                   $db->f("title"));

            $laststatus = getLastWorkflowStatus($idartlang);
    		$username = getGroupOrUserName($userids[$key]);
            $actionSelect = piworkflowRenderColumn($idcat, $idart, $db->f('idartlang'), 'wfaction');
            
            $currentUserSequence->loadByPrimaryKey($usersequence[$key]);
    		$workflowItem = $currentUserSequence->getWorkflowItem();
            $step = $workflowItem->get("name"); 
            $description = $workflowItem->get("description");
            
            $sRowId = $db->f('idart').'-'.$db->f('idartlang').'-'.$db->f('idcat').'-'.$db->f('idcatlang').'-'.$db->f('idcatart').'-'.$db->f('art_lang');
            
            if( $db->f('startidartlang') == $db->f('idartlang') ) {
			    $makeStartarticle = "<img src=\"images/isstart1.gif\" border=\"0\" title=\"{$sFlagTitle}\" alt=\"{$sFlagTitle}\">";
            } else {
                $makeStartarticle = "<img src=\"images/isstart0.gif\" border=\"0\" title=\"{$sFlagTitle}\" alt=\"{$sFlagTitle}\">";
            }
            
            $todoListeSubject = i18n("Reminder");
            $sReminder = i18n("Set reminder / add to todo list");
            $sReminderHtml = "<a id=\"m1\" onclick=\"javascript:window.open('main.php?subject=$todoListeSubject&amp;area=todo&amp;frame=1&amp;itemtype=idart&amp;itemid=$idart&amp;contenido=$sSession', 'todo', 'scrollbars=yes, height=300, width=550');\" alt=\"$sReminder\" title=\"$sReminder\" href=\"#\"><img id=\"m2\" style=\"padding-left: 2px; padding-right: 2px;\" alt=\"$sReminder\" src=\"images/but_setreminder.gif\" border=\"0\"></a>";
            
            $templatename = $db->f('tpl_name');
            if (!empty($templatename)) {
		        $templatename = htmlentities($templatename);
		    } else {
		        $templatename = '--- ' . i18n("None") . ' ---';
		    }
            
            if ($i == 0) {
                $iIDCat = $db->f("idcat");
                $iIDTpl = $idtpl;
                $tpl->set('s', 'FIRST_ROWID', $sRowId);
            }
            
            $tpl->set('d', 'START', $makeStartarticle);
            $tpl->set('d', 'TITLE', $mstr);     
            $tpl->set('d', 'LAST_STATUS', $laststatus);  
            $tpl->set('d', 'WORKFLOW_EDITOR', $username); 
            $tpl->set('d', 'WORKFLOW_STEP', $step); 
            $tpl->set('d', 'WORKFLOW_ACTION', $actionSelect); 
            $tpl->set('d', 'TEMPLATE', $templatename); 
            $tpl->set('d', 'BGCOLOR', $cfg['color']['table_dark_offline']);
            $tpl->set('d', 'ROWID', $sRowId);
            $tpl->set('d', 'ACTIONS', $sReminderHtml);
            $tpl->next();
            $i++;
    	}
	}
}
}

if ($i > 0) 	{
    $tpl->set('s', 'NO_ARTICLES_ROW');
} else {
    $sRow = '<tr><td colspan="8" class="bordercell">' . i18n("No article found.") . '</td></tr>';
    $tpl->set('s', 'NO_ARTICLES_ROW', $sRow);
}

$sLoadSubnavi = 'parent.parent.frames["right"].frames["right_top"].location.href = \'main.php?area=con&frame=3&idcat=' . $iIDCat . '&idtpl=' . $iIDTpl . '&contenido=' . $sSession . "';";
$tpl->set('s', 'SUBNAVI', $sLoadSubnavi);

$frame = ob_get_contents();
ob_end_clean();

$tpl->generate($cfg["path"]['contenido'] . $cfg["path"]["plugins"] . "workflow/templates/template.workflow_tasks.html");

?>
