<?php

/**
 * Copyright (C) 2008-2012 FluxBB
 * based on code by Rickard Andersson copyright (C) 2002-2008 PunBB
 * License: http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 */

// Tell header.php to use the admin template
define('PUN_ADMIN_CONSOLE', 1);

define('PUN_ROOT', dirname(__FILE__).'/');
require PUN_ROOT.'include/common.php';
require PUN_ROOT.'include/common_admin.php';


if ($pun_user['g_id'] != PUN_ADMIN)
	message($lang->t('No permission'));

// Load the admin_ranks.php language file
$lang->load('admin_ranks');

// Add a rank
if (isset($_POST['add_rank']))
{
	confirm_referrer('admin_ranks.php');

	$rank = pun_trim($_POST['new_rank']);
	$min_posts = trim($_POST['new_min_posts']);

	if ($rank == '')
		message($lang->t('Must enter title message'));

	if ($min_posts == '' || preg_match('%[^0-9]%', $min_posts))
		message($lang->t('Must be integer message'));

	// Make sure there isn't already a rank with the same min_posts value
	$query = $db->select(array('one' => '1'), 'ranks AS r');
	$query->where = 'r.min_posts = :min_posts';

	$params = array(':min_posts' => $min_posts);

	$result = $query->run($params);
	if (!empty($result))
		message($lang->t('Dupe min posts message', $min_posts));

	unset($query, $params, $result);

	$query = $db->insert(array('rank' => ':rank', 'min_posts' => ':min_posts'), 'ranks');

	$params = array(':rank' => $rank, ':min_posts' => $min_posts);

	$query->run($params);
	unset($query, $params);

	// Regenerate the ranks cache
	$cache->delete('ranks');

	redirect('admin_ranks.php', $lang->t('Rank added redirect'));
}


// Update a rank
else if (isset($_POST['update']))
{
	confirm_referrer('admin_ranks.php');

	$id = intval(key($_POST['update']));

	$rank = pun_trim($_POST['rank'][$id]);
	$min_posts = trim($_POST['min_posts'][$id]);

	if ($rank == '')
		message($lang->t('Must enter title message'));

	if ($min_posts == '' || preg_match('%[^0-9]%', $min_posts))
		message($lang->t('Must be integer message'));

	// Make sure there isn't already a rank with the same min_posts value
	$query = $db->select(array('one' => '1'), 'ranks AS r');
	$query->where = 'id != :id AND min_posts = :min_posts';

	$params = array(':id' => $id, ':min_posts' => $min_posts);

	$result = $query->run($params);
	if (!empty($result))
		message($lang->t('Dupe min posts message', $min_posts));

	unset($query, $params, $result);

	$query = $db->update(array('rank' => ':rank', 'min_posts' => ':min_posts'), 'ranks');
	$query->where = 'id = :id';

	$params = array(':rank' => $rank, ':min_posts' => $min_posts, ':id' => $id);

	$query->run($params);
	unset($query, $params);

	// Regenerate the ranks cache
	$cache->delete('ranks');

	redirect('admin_ranks.php', $lang->t('Rank updated redirect'));
}


// Remove a rank
else if (isset($_POST['remove']))
{
	confirm_referrer('admin_ranks.php');

	$id = intval(key($_POST['remove']));

	$query = $db->delete('ranks');
	$query->where = 'id = :id';

	$params = array(':id' => $id);

	$query->run($params);
	unset($query, $params);

	// Regenerate the ranks cache
	$cache->delete('ranks');

	redirect('admin_ranks.php', $lang->t('Rank removed redirect'));
}

$page_title = array(pun_htmlspecialchars($pun_config['o_board_title']), $lang->t('Admin'), $lang->t('Ranks'));
$focus_element = array('ranks', 'new_rank');
define('PUN_ACTIVE_PAGE', 'admin');
require PUN_ROOT.'header.php';

generate_admin_menu('ranks');

?>
	<div class="blockform">
		<h2><span><?php echo $lang->t('Ranks head') ?></span></h2>
		<div class="box">
			<form id="ranks" method="post" action="admin_ranks.php">
				<div class="inform">
					<fieldset>
						<legend><?php echo $lang->t('Add rank subhead') ?></legend>
						<div class="infldset">
							<p><?php echo $lang->t('Add rank info').' '.($pun_config['o_ranks'] == '1' ? $lang->t('Ranks enabled', '<a href="admin_options.php#ranks">'.$lang->t('Options').'</a>') : $lang->t('Ranks disabled', '<a href="admin_options.php#ranks">'.$lang->t('Options').'</a>')) ?></p>
							<table cellspacing="0">
							<thead>
								<tr>
									<th class="tcl" scope="col"><?php echo $lang->t('Rank title label') ?></th>
									<th class="tc2" scope="col"><?php echo $lang->t('Minimum posts label') ?></th>
									<th class="hidehead" scope="col"><?php echo $lang->t('Actions label') ?></th>
								</tr>
							</thead>
							<tbody>
								<tr>
									<td class="tcl"><input type="text" name="new_rank" size="24" maxlength="50" tabindex="1" /></td>
									<td class="tc2"><input type="text" name="new_min_posts" size="7" maxlength="7" tabindex="2" /></td>
									<td><input type="submit" name="add_rank" value="<?php echo $lang->t('Add') ?>" tabindex="3" /></td>
								</tr>
							</tbody>
							</table>
						</div>
					</fieldset>
				</div>
				<div class="inform">
					<fieldset>
						<legend><?php echo $lang->t('Edit remove subhead') ?></legend>
						<div class="infldset">
<?php

$query = $db->select(array('id' => 'r.id', 'rank' => 'r.rank', 'min_posts' => 'r.min_posts'), 'ranks AS r');
$query->order = array('min_posts' => 'r.min_posts ASC');

$result = $query->run();
unset($query);

if (!empty($result))
{

?>
							<table cellspacing="0">
							<thead>
								<tr>
									<th class="tcl" scope="col"><?php echo $lang->t('Rank title label') ?></th>
									<th class="tc2" scope="col"><?php echo $lang->t('Minimum posts label') ?></th>
									<th class="hidehead" scope="col"><?php echo $lang->t('Actions label') ?></th>
								</tr>
							</thead>
							<tbody>
<?php

	foreach ($result as $cur_rank)
		echo "\t\t\t\t\t\t\t\t".'<tr><td class="tcl"><input type="text" name="rank['.$cur_rank['id'].']" value="'.pun_htmlspecialchars($cur_rank['rank']).'" size="24" maxlength="50" /></td><td class="tc2"><input type="text" name="min_posts['.$cur_rank['id'].']" value="'.$cur_rank['min_posts'].'" size="7" maxlength="7" /></td><td><input type="submit" name="update['.$cur_rank['id'].']" value="'.$lang->t('Update').'" />&#160;<input type="submit" name="remove['.$cur_rank['id'].']" value="'.$lang->t('Remove').'" /></td></tr>'."\n";

?>
							</tbody>
							</table>
<?php

}
else
	echo "\t\t\t\t\t\t\t".'<p>'.$lang->t('No ranks in list').'</p>'."\n";

unset($result);

?>
						</div>
					</fieldset>
				</div>
			</form>
		</div>
	</div>
	<div class="clearer"></div>
</div>
<?php

require PUN_ROOT.'footer.php';
