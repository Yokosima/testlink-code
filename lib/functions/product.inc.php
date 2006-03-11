<?php
/** TestLink Open Source Project - http://testlink.sourceforge.net/ 
 * 
 * @filesource $RCSfile: product.inc.php,v $
 * @version $Revision: 1.14 $
 * @modified $Date: 2006/03/11 23:09:19 $
 * @author Martin Havlat
 *
 * Functions for Product management (create,update,delete)
 * Functions for get data see product.core.inc.php
 *
 * @author: francisco mancardi - 20060101 - product notes management
 *
 */
require_once('product.core.inc.php');

/**
 * delete a product including all dependent data
 * 
 * @param integer $id
 * @param pointer Problem message
 * @return boolean 1=ok || 0=ko
 */
// MHT 20050630 added to delete all nested data
/** @todo the function are not able to delete test plan data from another product (i.e. test case suite) */
function deleteProduct(&$db,$id, &$error)
{
	$error = ''; //clear error string
	
	// list of sql commands + fail info id
	// be aware order of delete commands (there are dependencies)
	$arrExecSql = array (
		// delete bugs
		// 20051005 - am - non-existing build-column was used
		array ("DELETE bugs FROM bugs,testplans,build WHERE build.projid=testplans.id" .
			" AND bugs.build_id=build.id AND testplans.prodid=" . $id, 
			'info_bugs_delete_fails'),
		// delete builds
		array ("DELETE build FROM testplans,build WHERE build.projid=testplans.id" .
			" AND testplans.prodid=" . $id, 
			'info_build_delete_fails'),
		// delete milestones
		array ("DELETE milestone FROM testplans,milestone WHERE milestone.projid=testplans.id" .
				" AND testplans.prodid=" . $id, 
			'info_milestones_delete_fails'),
		// delete priority
		array ("DELETE priority FROM testplans,priority WHERE priority.projid=testplans.id" .
				" AND testplans.prodid=" . $id, 
			'info_priority_delete_fails'),
		// delete Test Plan rights
		// 20060224 - franciscom
		//array ("DELETE testplans_rights FROM testplans,testplans_rights WHERE testplans_rights.projid=testplans.id" .
		//		" AND testplans.prodid=" . $id, 
		//	'info_plan_rights_delete_fails'),
		// delete test plans - should not be deleted if nested data were not deleted
		array ("DELETE FROM testplans WHERE prodid=" . $id, 
			'info_testplan_delete_fails'),

		// delete results
		array ("DELETE results FROM results,testcase," .
			"mgtcomponent,mgtcategory,mgttestcase WHERE mgtcomponent.prodid=" . $id .
			" AND mgtcomponent.id=mgtcategory.compid AND mgtcategory.id=mgttestcase.catid" .
			" AND testcase.mgttcid=mgttestcase.id AND results.tcid=testcase.id", 
			'info_results_delete_fails'),
		// delete test case suites
		array ("DELETE testcase,category,component FROM testcase,category,component," .
			"mgtcomponent,mgtcategory,mgttestcase WHERE mgtcomponent.prodid=" . $id .
			" AND mgtcomponent.id=mgtcategory.compid AND mgtcategory.id=mgttestcase.catid" .
			" AND testcase.mgttcid=mgttestcase.id AND mgtcategory.id=category.mgtcatid" .
			" AND component.mgtcompid=mgtcomponent.id", 
			'info_testsuite_delete_fails'),
		// delete test specification
		array ("DELETE mgttestcase,mgtcategory,mgtcomponent FROM mgtcomponent," .
			"mgtcategory,mgttestcase WHERE mgtcomponent.prodid=" . $id .
			" AND mgtcomponent.id=mgtcategory.compid AND mgtcategory.id=mgttestcase.catid", 
			'info_testspec_delete_fails'),

		// delete keywords
		// 20051005 - am - wrong column used for deleting keywords
		array ("DELETE FROM keywords WHERE prodid=" . $id, 
			'info_keywords_delete_fails'),
		// delete requirements
		array ("DELETE req_spec,requirements,req_coverage FROM req_spec,requirements,req_coverage " .
			"WHERE req_spec.product_id=" . $id . " AND req_spec.id=requirements.srs_id" .
			" AND req_coverage.req_id=requirements.id", 
			'info_reqs_delete_fails'),

		array ("DELETE FROM user_testproject_roles WHERE testproject_id=" . $id, 
			'info_productrights_delete_fails'),
	); 

	// delete all nested data over array $arrExecSql
	foreach ($arrExecSql as $oneSQL)
	{
		if (empty($error))
		{
			tLog($oneSQL[0]);
			$sql = $oneSQL[0];
			$result = $db->exec_query($sql);	
			if (!$result) {
				$error .= lang_get($oneSQL[1]);
			}
		}
	}	

	// delete product itself
	if (empty($error))
	{
		$sql = "DELETE FROM mgtproduct WHERE id=" . $id;
		$result = $db->exec_query($sql);

		if ($result) {
			$tproject_id_on_session = isset($_SESSION['testprojectID']) ? $_SESSION['testproject'] : $id;
			if ($id == $tproject_id_on_session) {
				setSessionTestProject(null);
			}
		} else {
			$error .= lang_get('info_product_delete_fails');
		}
	}

	return empty($error) ? 1 : 0;
}
?>
