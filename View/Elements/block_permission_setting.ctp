<?php
/**
 * ブロックのパーミッション設定Element
 * WorkflowComponent->getBlockRolePermissions())で取得した結果をもとにセットする
 *
 * - settingPermissions: 設定するパーミッションデータ(key: パーミッション名、value: ラベル)
 *
 * ### サンプル
 * ```
 * 	echo $this->element('Blocks.block_permission_setting', array(
 *			'settingPermissions' => array(
 *				'mail_content_receivable' => __d('mails', 'Notification to the authority'),
 *			)
 *		);
 * ```
 * ```
 * 	echo $this->element('Blocks.block_permission_setting', array(
 *			'settingPermissions' => array(
 *				'mail_content_receivable' => [
 *					'label' => __d('mails', 'Notification to the authority'),
 *					'help' => 'ヘルプブロック' or false
 *				],
 *			)
 *		);
 * ```
 *
 * @author Noriko Arai <arai@nii.ac.jp>
 * @author Shohei Nakajima <nakajimashouhei@gmail.com>
 * @author Mitsuru Mutaguchi <mutaguchi@opensource-workshop.jp>
 * @link http://www.netcommons.org NetCommons Project
 * @license http://www.netcommons.org/license.txt NetCommons License
 * @copyright Copyright 2014, NetCommons Project
 */

echo $this->NetCommonsHtml->script('/blocks/js/block_role_permissions.js');

//Camel形式に変換
$initializeParams = NetCommonsAppController::camelizeKeyRecursive(array('roles' => $roles));
?>

<div ng-controller="BlockRolePermissions"
	ng-init="initializeRoles(<?php echo h(json_encode($initializeParams, JSON_FORCE_OBJECT)); ?>)">

	<?php foreach ($settingPermissions as $permission => $label) : ?>
		<div class="form-group">
			<?php
				$help = false;
				if (is_array($label)) {
					$message = $label;
					$label = Hash::get($message, 'label', '');
					$help = Hash::get($message, 'help', false);
				}
			?>
			<?php echo $this->NetCommonsForm->label('BlockRolePermission.' . $permission, h($label)); ?>
			<?php echo $this->BlockRolePermissionForm->checkboxBlockRolePermission('BlockRolePermission.' . $permission); ?>
			<?php if ($help) : ?>
				<?php echo $this->NetCommonsForm->help($help); ?>
			<?php endif; ?>
		</div>
	<?php endforeach; ?>
</div>
