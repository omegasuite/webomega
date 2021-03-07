<style>
	.detailmodal-width	{
		width: -webkit-fit-content;
		width: -moz-fit-content;
		width: fit-content;    
	}
</style>

<div class="modal fade" role="dialog" id="message" tabindex="-1"  aria-labelledby="orderdetailLabel" aria-hidden="true">
	<div class="modal-dialog detailmodal-width">
		<div class="modal-content">
			<div class="modal-header">
				<button type="button" class="close" data-dismiss="modal" aria-hidden="true">
					&times;
				</button>
				<h4 class="modal-title" id="orderdetailLabel">
					<div id='title'></div>
				</h4>
			</div>
			<div class="modal-body" id=messagecontent>
			</div>
		</div><!-- /.modal-content -->
	</div>
</div>

{LIST}