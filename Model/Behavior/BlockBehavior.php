<?php
/**
 * Block Behavior
 *
 * @author Noriko Arai <arai@nii.ac.jp>
 * @author Shohei Nakajima <nakajimashouhei@gmail.com>
 * @link http://www.netcommons.org NetCommons Project
 * @license http://www.netcommons.org/license.txt NetCommons License
 * @copyright Copyright 2014, NetCommons Project
 */

App::uses('ModelBehavior', 'Model');

/**
 * Block Behavior
 *
 * @author Shohei Nakajima <nakajimashouhei@gmail.com>
 * @package NetCommons\Blocks\Model\Behavior
 */
class BlockBehavior extends ModelBehavior {

/**
 * beforeValidate is called before a model is validated, you can use this callback to
 * add behavior validation rules into a models validate array. Returning false
 * will allow you to make the validation fail.
 *
 * @param Model $model Model using this behavior
 * @param array $options Options passed from Model::save().
 * @return mixed False or null will abort the operation. Any other result will continue.
 * @see Model::save()
 */
	public function beforeValidate(Model $model, $options = array()) {
		if (! isset($model->data['Block'])) {
			return true;
		}

		$model->loadModels(array(
			'Block' => 'Blocks.Block',
		));
		$model->Block->set($model->data['Block']);
		$model->Block->validates();
		if ($model->Block->validationErrors) {
			$model->validationErrors = Hash::merge($model->validationErrors, $model->Block->validationErrors);
			return false;
		}

		return true;
	}

/**
 * beforeSave is called before a model is saved. Returning false from a beforeSave callback
 * will abort the save operation.
 *
 * @param Model $model Model using this behavior
 * @param array $options Options passed from Model::save().
 * @return mixed False if the operation should abort. Any other result will continue.
 * @see Model::save()
 */
	public function beforeSave(Model $model, $options = array()) {
		if (! isset($model->data['Frame']['id'])) {
			return parent::beforeSave($model, $options);
		}

		$model->loadModels(array(
			'Block' => 'Blocks.Block',
			'Frame' => 'Frames.Frame',
		));

		//frameの取得
		$frame = $model->Frame->findById($model->data['Frame']['id']);
		if (! $frame) {
			throw new InternalErrorException(__d('net_commons', 'Internal Server Error'));
		}

		if (! isset($model->data['Block'])) {
			if ($frame['Frame']['block_id']) {
				return parent::beforeSave($model, $options);
			}
			$model->data['Block']['room_id'] = $frame['Frame']['room_id'];
			$model->data['Block']['language_id'] = $frame['Frame']['language_id'];
			$model->data['Block']['name'] = '';
		}
		if (! $model->data['Block']['name']) {
			$model->data['Block']['name'] = sprintf(__d('blocks', 'Block %s'), date('YmdHis'));
		}
		if (! isset($model->data['Block']['plugin_key']) || ! $model->data['Block']['plugin_key']) {
			$model->data['Block']['plugin_key'] = Inflector::underscore($model->plugin);
		}

		//blocksの登録
		if (! $block = $model->Block->save($model->data['Block'], false)) {
			throw new InternalErrorException(__d('net_commons', 'Internal Server Error'));
		}
		$model->data['Block'] = $block['Block'];

		//framesテーブル更新
		if (! $frame['Frame']['block_id']) {
			$frame['Frame']['block_id'] = (int)$block['Block']['id'];
			if (! $model->Frame->save($frame, false)) {
				throw new InternalErrorException(__d('net_commons', 'Internal Server Error'));
			}
		}

		foreach ($model->data as $name => $data) {
			if (isset($data['block_id'])) {
				$model->data[$name]['block_id'] = $model->data['Block']['id'];
			}
			if (isset($data['block_key'])) {
				$model->data[$name]['block_key'] = $model->data['Block']['key'];
			}
		}

		return parent::beforeSave($model, $options);
	}

/**
 * Create block data
 *
 * @param int $blockId blocks.id
 * @param array $options Create options array
 * @param bool $created If True, the results of the Model::find() to create it if it was null
 * @return array
 */
	public function createBlock(Model $model, $options = array()) {
		$options = Hash::merge(array(
			'id' => null,
			'key' => null,
			'name' => null,
			'language_id' => Configure::read('Config.languageId'),
			'room_id' => null,
			'plugin_key' => Inflector::underscore($model->plugin)
		), $options);

		$block = $model->Block->create($options);

		return $block;
	}

/**
 * Get Block data
 *
 * @param Model $model Model using this behavior
 * @param string $blockId blocks.id
 * @param int $roomId rooms.id
 * @return array Block data
 */
	public function getBlock(Model $model, $blockId, $roomId) {
		$model->Block = ClassRegistry::init('Blocks.Block');

		$block = $model->Block->find('first', array(
			'recursive' => -1,
			'conditions' => array(
				'Block.id' => $blockId,
				'Block.room_id' => $roomId,
			)
		));

		return $block;
	}

/**
 * Delete block.
 *
 * @param Model $model Model using this behavior
 * @param string $key blocks.key
 * @return void
 * @throws InternalErrorException
 */
	public function deleteBlock(Model $model, $key) {
		$model->loadModels([
			'Block' => 'Blocks.Block',
			'BlockRolePermission' => 'Blocks.BlockRolePermission',
			'Frame' => 'Frames.Frame',
		]);

		//Blockデータ取得
		$conditions = array(
			$model->Block->alias . '.key' => $key
		);
		$blocks = $model->Block->find('list', array(
			'recursive' => -1,
			'conditions' => $conditions,
		));

		//Blockデータ削除
		if (! $model->Block->deleteAll($conditions, false)) {
			throw new InternalErrorException(__d('net_commons', 'Internal Server Error'));
		}

		//BlockRolePermissionデータ削除
		if (! $model->BlockRolePermission->deleteAll(array($model->BlockRolePermission->alias . '.block_key' => $key), false)) {
			throw new InternalErrorException(__d('net_commons', 'Internal Server Error'));
		}

		$blocks = array_keys($blocks);
		foreach ($blocks as $blockId) {
			if (! $model->Frame->updateAll(
					array('Frame.block_id' => null),
					array('Frame.block_id' => (int)$blockId)
			)) {
				throw new InternalErrorException(__d('net_commons', 'Internal Server Error'));
			}
		}
	}

}
