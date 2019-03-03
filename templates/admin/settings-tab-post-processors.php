<?php
/**
 * This template renders a single field.
 *
 * @package Antispam Bee Templates
 */

if ( ! isset( $tab_data ) ) {
	return;
}
$active_processors = $tab_data->active_processors;
$tab_type          = $tab_data->type;
/**
 * The active post processors.
 *
 * @var array $active_processors
 */
?>

<form method="post" action="#">
	<input
		type="hidden"
		name="<?php echo esc_attr( $tab_data->nonce_name ); ?>"
		value="<?php echo esc_attr( $tab_data->nonce ); ?>"
	/>
	<input
		type="hidden"
		name="type"
		value="<?php echo esc_attr( $tab_data->type ); ?>"
	/>
	<ul>
		<?php
		foreach ( $tab_data->processors as $processor ) :
			/**
			 * The current processor.
			 *
			 * @var \Pluginkollektiv\AntispamBee\PostProcessor\PostProcessorInterface $processor
			 */
			$type = $processor->id();
			?>

			<li>
				<?php if ( $processor->options()->activateable() ) : ?>
					<select
						id="post-processor-<?php echo esc_attr( $processor->id() ); ?>"
						type="checkbox"
						name="antispambee_fields[<?php echo esc_attr( $processor->id() ); ?>]"
					>
						<option
							value="0"
							<?php selected( ! in_array( $processor->id(), $active_processors, true ) ); ?>
						>
							<?php esc_html_e( 'Off', 'antispam-bee' ); ?>
						</option>
						<option
							value="1"
							<?php selected( in_array( $processor->id(), $active_processors, true ) ); ?>
						>
							<?php esc_html_e( 'On', 'antispam-bee' ); ?>
						</option>
					</select>
				<?php endif; ?>
				<h3>

					<?php if ( $processor->options()->activateable() ) : ?>
						<label
							for="post-processor-<?php echo esc_attr( $processor->id() ); ?>"
						>
							<?php echo esc_html( $processor->options()->name() ); ?>
						</label>
					<?php else : ?>
						<?php echo esc_html( $processor->options()->name() ); ?>
					<?php endif; ?>
				</h3>
				<p>
					<?php echo esc_html( $processor->options()->description() ); ?>
				</p>

				<?php foreach ( $processor->options()->fields() as $field ) : ?>
					<?php include __DIR__ . '/field.php'; ?>
				<?php endforeach; ?>
			</li>
		<?php endforeach; ?>
	</ul>
	<?php submit_button(); ?>
</form>