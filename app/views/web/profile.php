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
use DreamFactory\Yii\Utility\Validate;

/**
 * @var WebController $this
 * @var ProfileForm   $model
 * @var CActiveForm   $form
 * @var string        $backUrl
 */

Validate::register(
	'form#profile-form',
	array(
		 'ignoreTitle'    => true,
		 'errorClass'     => 'error',
		 'errorPlacement' => 'function(error,element){error.appendTo(element.closest("div.form-group"));error.css("margin","-10px 0 0");}',
		 'rules'          => array(
			 'ProfileForm[email]'        => array(
				 'required'  => true,
				 'minlength' => 5,
			 ),
			 'ProfileForm[first_name]'   => array(
				 'required' => true,
			 ),
			 'ProfileForm[last_name]'    => array(
				 'required' => true,
			 ),
			 'ProfileForm[display_name]' => array(
				 'required' => true,
			 ),
		 ),
	)
);
?>
<div class="container" id="formbox">
	<h2>User Profile</h2>

	<?php
	$form = $this->beginWidget(
		'CActiveForm',
		array(
			 'id'                     => 'profile-form',
			 'enableClientValidation' => true,
			 'clientOptions'          => array(
				 'validateOnSubmit' => true,
			 ),
		)
	);
	?>

	<?php if ( Yii::app()->user->hasFlash( 'profile-form' ) ): ?>

		<div class="alert alert-success">
			<?php echo Yii::app()->user->getFlash( 'profile-form' ); ?>
		</div>

	<?php endif; ?>

	<div class="form-group">
		<label for="ProfileForm_email" class="sr-only">Email Address</label>

		<div class="input-group">
			<span class="input-group-addon bg_dg"><i class="fa fa-envelope fa-fw"></i></span>

			<input tabindex="1" class="form-control email required" autofocus type="email" id="ProfileForm_email"
				   name="ProfileForm[email]" placeholder="Email Address"
				   value="<?php echo( $model->email ? $model->email : '' ); ?>" />
		</div>
	</div>
	<div class="form-group">
		<label for="ProfileForm_firstName" class="sr-only">First Name</label>

		<div class="input-group">
			<span class="input-group-addon bg_dg"><i class="fa fa-user fa-fw"></i></span>

			<input tabindex="2" class="form-control required" type="text" id="ProfileForm_firstName"
				   name="ProfileForm[first_name]" placeholder="First Name"
				   value="<?php echo( $model->first_name ? $model->first_name : '' ); ?>" />
		</div>
	</div>
	<div class="form-group">
		<label for="ProfileForm_lastName" class="sr-only">Last Name</label>

		<div class="input-group">
			<span class="input-group-addon bg_dg"><i class="fa fa-user fa-fw"></i></span>

			<input tabindex="3" class="form-control required" type="text" id="ProfileForm_lastName"
				   name="ProfileForm[last_name]" placeholder="Last Name"
				   value="<?php echo( $model->last_name ? $model->last_name : '' ); ?>" />
		</div>
	</div>
	<div class="form-group">
		<label for="ProfileForm_displayName" class="sr-only">Display Name</label>

		<div class="input-group">
			<span class="input-group-addon bg_dg"><i class="fa fa-eye fa-fw"></i></span>

			<input tabindex="4" class="form-control" type="text" id="ProfileForm_displayName"
				   name="ProfileForm[display_name]" placeholder="Display Name"
				   value="<?php echo( $model->display_name ? $model->display_name : '' ); ?>" />
		</div>
	</div>
	<div class="form-group">
		<label for="ProfileForm_phone" class="sr-only">Phone</label>

		<div class="input-group">
			<span class="input-group-addon bg_dg"><i class="fa fa-phone fa-fw"></i></span>

			<input tabindex="5" class="form-control" type="text" id="ProfileForm_phone"
				   name="ProfileForm[phone]" placeholder="Phone"
				   value="<?php echo( $model->phone ? $model->phone : '' ); ?>" />
		</div>
	</div>
	<div class="form-group">
		<label for="ProfileForm_securityQuestion" class="sr-only">Security Question</label>

		<div class="input-group">
			<span class="input-group-addon bg_dg"><i class="fa fa-question fa-fw"></i></span>

			<input tabindex="6" class="form-control" type="text" id="ProfileForm_securityQuestion"
				   name="ProfileForm[security_question]" placeholder="Security Question"
				   value="<?php echo( $model->security_question ? $model->security_question : '' ); ?>" />
		</div>
	</div>
	<div class="form-group">
		<label for="ProfileForm_securityAnswer" class="sr-only">Security Answer</label>

		<div class="input-group">
			<span class="input-group-addon bg_dg"><i class="fa fa-question fa-fw"></i></span>

			<input tabindex="7" class="form-control" type="text" id="ProfileForm_securityAnswer"
				   name="ProfileForm[security_answer]" placeholder="Security Answer"
				   value="<?php echo( $model->security_answer ? $model->security_answer : '' ); ?>" />
		</div>
	</div>
	<div class="form-group">
		<label for="ProfileForm_defaultApp" class="sr-only">Default Application</label>

		<div class="input-group">
			<span class="input-group-addon bg_dg"><i class="fa fa-cloud fa-fw"></i></span>

			<select tabindex="8" class="form-control" id="ProfileForm_defaultApp" name="ProfileForm[default_app_id]">
				<option value=null>None</option>
				<?php
				echo "<option value=null>None</option>";
				?>
			</select>
		</div>
	</div>

	<?php echo $form->errorSummary( $model ); ?>

	<div class="form-buttons">
		<button type="submit" class="btn btn-success pull-right">Save</button>
		<button type="button" id="btn-back" class="btn btn-default pull-left">Back</button>
	</div>

	<?php $this->endWidget(); ?>
</div>
<script type="text/javascript">
jQuery(function($) {
	$('#profile-form').on('focus', 'input#ProfileForm_displayName', function(e) {
		if (!$('#ProfileForm_displayName').val().trim().length) {
			$('#ProfileForm_displayName').val($('#ProfileForm_firstName').val() + ' ' + $('#ProfileForm_lastName').val())
		}
	});
	$('#btn-back').on('click', function(e) {
		e.preventDefault();
		window.location = '<?php echo $backUrl?>';
	});
});
</script>
