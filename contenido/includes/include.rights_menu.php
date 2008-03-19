<?php
/******************************************
* File      :   include.rights_menu.php
* Project   :   Contenido
* Descr     :   Displays languages
*
* Author    :   Olaf Niemann
* Created   :   23.04.2003
* Modified  :   23.04.2003
*
* � four for business AG
*****************************************/
cInclude("classes", "widgets/class.widgets.page.php");
cInclude("classes", "widgets/class.widgets.foldingrow.php");
cInclude("classes", "widgets/class.widgets.pager.php");
cInclude("classes", "class.ui.php");

$oPage = new cPage;

$cApiUserCollection = new cApiUserCollection;

if (isset($_REQUEST["sortby"]) && $_REQUEST["sortby"] != "")
{
	$cApiUserCollection->setOrder($_REQUEST["sortby"]. " ". $_REQUEST["sortorder"]);	
} else {
	$cApiUserCollection->setOrder("username asc");
}

if (isset($_REQUEST["filter"]) && $_REQUEST["filter"] != "")
{
	$cApiUserCollection->setWhereGroup("default", "username", "%".$_REQUEST["filter"]."%", "LIKE");	
	$cApiUserCollection->setWhereGroup("default", "realname", "%".$_REQUEST["filter"]."%", "LIKE");
	$cApiUserCollection->setWhereGroup("default", "email", "%".$_REQUEST["filter"]."%", "LIKE");
	$cApiUserCollection->setWhereGroup("default", "telephone", "%".$_REQUEST["filter"]."%", "LIKE");
	$cApiUserCollection->setWhereGroup("default", "address_street", "%".$_REQUEST["filter"]."%", "LIKE");
	$cApiUserCollection->setWhereGroup("default", "address_zip", "%".$_REQUEST["filter"]."%", "LIKE");
	$cApiUserCollection->setWhereGroup("default", "address_city", "%".$_REQUEST["filter"]."%", "LIKE");
	$cApiUserCollection->setWhereGroup("default", "address_country", "%".$_REQUEST["filter"]."%", "LIKE");
	
	$cApiUserCollection->setInnerGroupCondition("default", "OR");
}
$cApiUserCollection->query();

$aCurrentUserPermissions = split(",", $auth->auth["perm"]);
$aCurrentUserAccessibleClients = $classclient->getAccessibleClients();

$iMenu = 0;
$iItemCount = 0;
$mPage = $_REQUEST["page"];

if ($mPage == 0)
{
	$mPage = 1;	
}

$elemperpage = $_REQUEST["elemperpage"];

if ($elemperpage == 0)
{
	$elemperpage = 25;
}

$mlist = new UI_Menu;
$sToday = date('Y-m-d');

while ($cApiUser = $cApiUserCollection->next())
{
	$userid = $cApiUser->get("user_id");
	
	$aUserPermissions = split(",", $cApiUser->get("perms"));
	
	$bDisplayUser = false;

    if (in_array("sysadmin", $aCurrentUserPermissions))
    {
        $bDisplayUser = true;
    }
    
    foreach ($aCurrentUserAccessibleClients as $key => $value)
    {
        if (in_array("client[$key]", $aUserPermissions))
        {
            $bDisplayUser = true;
        }
    }
    
    foreach ($aUserPermissions as $sLocalPermission)
    {
        if (in_array($sLocalPermission, $aCurrentUserPermissions))
        {
            $bDisplayUser = true;
        }
    }    
    
    $link = new cHTMLLink;
    $link->setMultiLink("user", "", "user_overview", "");
    $link->setCustom("userid", $cApiUser->get("user_id"));
    
    if ($bDisplayUser == true)
    {
    	$iItemCount++;

    	if ($iItemCount > ($elemperpage * ($mPage - 1)) && $iItemCount < (($elemperpage * $mPage) + 1))
    	{
	        if ($perm->have_perm_area_action('user',"user_delete") ) { 
	        		$message = sprintf(i18n("Do you really want to delete the user %s?"), $username);
	        		
					$delTitle = i18n("Delete user");
					$deletebutton = '<a title="'.$delTitle.'" href="javascript://" onclick="box.confirm(\''.$delTitle.'\', \''.$message.'\', \'deleteBackenduser(\\\''.$userid.'\\\')\')"><img src="'.$cfg['path']['images'].'delete.gif" border="0" title="'.$delTitle.'" alt="'.$delTitle.'"></a>';
				        		
	            } else {
	                $deletebutton = "";
	            }

	    	$iMenu++;
            
            if (($sToday < $cApiUser->get("valid_from") && ($cApiUser->get("valid_from") != '0000-00-00' && $cApiUser->get("valid_from") != '')) ||
                ($sToday > $cApiUser->get("valid_to") && ($cApiUser->get("valid_to") != '0000-00-00') && $cApiUser->get("valid_from") != '')) {
                $mlist->setTitle($iMenu, '<span style="color:#b3b3b8">'.$cApiUser->get("username")."<br>".$cApiUser->get("realname").'</span>');
            }  else {
                $mlist->setTitle($iMenu, $cApiUser->get("username")."<br>".$cApiUser->get("realname"));
            }            

	    	$mlist->setLink($iMenu, $link);		
	    	$mlist->setActions($iMenu, "delete", $deletebutton); 
            
            if ($_GET['userid'] == $cApiUser->get("user_id")) {
                $mlist->setExtra($iMenu, 'id="marked" ');
            }
    	}
    }
	
}

$deleteScript = '<script type="text/javascript">

        /* Session-ID */
        var sid = "'.$sess->id.'";

        /* Create messageBox
           instance */
        box = new messageBox("", "", "", 0, 0);

        /* Function for deleting
           modules */

        function deleteBackenduser(userid) {

			form = parent.parent.left.left_top.document.filter;

            url  = \'main.php?area=user_overview\';
            url += \'&action=user_delete\';
            url += \'&frame=4\';
            url += \'&userid=\' + userid;
            url += \'&contenido=\' + sid;
            url += get_registered_parameters();
            url += \'&sortby=\' +form.sortby.value;
			url += \'&sortorder=\' +form.sortorder.value;
			url += \'&filter=\' +form.filter.value;
			url += \'&elemperpage=\' +form.elemperpage.value;
			url += \'&page=\' +\''.$mPage.'\';
			parent.parent.right.right_bottom.location.href = url;
			parent.parent.right.right_top.location.href = \'main.php?area=user&frame=3&contenido=\'+sid;

        }

    </script>';
    
$markActiveScript = '<script type="text/javascript">
                         if (document.getElementById(\'marked\')) {
                             row.markedRow = document.getElementById(\'marked\');
                         }
                    </script>';
    //<script type="text/javascript" src="scripts/rowMark.js"></script>
$oPage->setMargin(0);
$oPage->addScript('rowMark.js', '<script language="JavaScript" src="scripts/rowMark.js"></script>');
$oPage->addScript('parameterCollector.js', '<script language="JavaScript" src="scripts/parameterCollector.js"></script>');
$oPage->addScript('messagebox', '<script type="text/javascript" src="scripts/messageBox.js.php?contenido='.$sess->id.'"></script>');
$oPage->addScript('delete', $deleteScript);
$oPage->setContent($mlist->render(false).$markActiveScript);
$oPage->render();

?>
