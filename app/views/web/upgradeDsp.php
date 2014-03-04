<?php
/**
 * This file is part of the DreamFactory Services Platform(tm) (DSP)
 *
 * DreamFactory Services Platform(tm) <http://github.com/dreamfactorysoftware/dsp-core>
 * Copyright 2012-2014 DreamFactory Software, Inc. <support@dreamfactory.com>
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 * http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */
/* @var $this WebController */
/* @var $model UpgradeDspForm */
/* @var $form CActiveForm */

$this->pageTitle = Yii::app()->name . ' - Upgrade';
$this->breadcrumbs = array(
	'Upgrade DSP',
);
?>

<div class="container" id="formbox">
	<h2>DreamFactory Services Platform&trade; Upgrade</h2>

	<?php if ( Yii::app()->user->hasFlash( 'upgrade-dsp' ) ): ?>

		<div class="flash-success">
			<?php echo Yii::app()->user->getFlash( 'upgrade-dsp' ); ?>
		</div>

	<?php elseif ( empty( $model->versions ) ): ?>

		<p>This DSP is currently running the latest available version. </p>

		<div class="form-buttons">
			<button type="button" id="btn-home" class="btn btn-default pull-left">Home</button>
		</div>

	<?php
	else: ?>

		<p>There is a software update available for this DSP. <br />
			When you are ready, select the desired version and click the 'Upgrade' button below. </p>

		<?php $form = $this->beginWidget(
			'CActiveForm',
			array(
				 'id'                     => 'upgrade-dsp-form',
				 'enableClientValidation' => true,
				 'clientOptions'          => array(
					 'validateOnSubmit' => true,
				 ),
			)
		); ?>

		<div class="row">
			<?php echo $form->hiddenField( $model, 'dummy' ); ?>
		</div>
		<div class="form-group">
			<?php echo $form->dropDownList( $model, 'selected', $model->versions, array( 'class' => 'btn' ) ); ?>
		</div>

		<?php echo $form->errorSummary( $model ); ?>

		<div class="form-buttons">
			<button type="button" id="btn-home" class="btn btn-default pull-left">Home</button>
			<button type="submit" class="btn btn-success pull-right">Upgrade</button>
		</div>

		<?php $this->endWidget(); ?>

	<?php endif; ?>
</div>
<script type="text/javascript">
jQuery(function($) {
	$('#btn-home').on('click', function(e) {
		e.preventDefault();
		window.location.href = '/';
	});
});
</script>