/**
 * Unstoppable Memberships — Quick Edit population.
 *
 * Core reuses one blank Quick Edit form for every row, so we read the saved
 * value from the row's hidden data element and inject it when the panel opens.
 *
 * @package Unstoppable_Memberships
 */
(function ($) {
	'use strict';

	if (!window.inlineEditPost || !inlineEditPost.edit) {
		return;
	}

	function toggleLevels($edit, access) {
		$edit.find('.umem-qe-levels').toggle(access === 'levels');
	}

	var original = inlineEditPost.edit;

	inlineEditPost.edit = function (id) {
		original.apply(this, arguments);

		var postId = 0;
		if (typeof id === 'object') {
			postId = parseInt(this.getId(id), 10);
		}
		if (!postId) {
			return;
		}

		var $data = $('#umem_inline_' + postId);
		var $edit = $('#edit-' + postId);
		if (!$data.length || !$edit.length) {
			return;
		}

		var access = String($data.data('access') || 'public');
		var levels = String($data.data('levels') || '').split(',').filter(Boolean);

		var $access = $edit.find('select[name="umem_qe[access]"]');
		$access.val(access);

		$edit.find('input[name="umem_qe[levels][]"]').each(function () {
			$(this).prop('checked', levels.indexOf($(this).val()) !== -1);
		});

		toggleLevels($edit, access);

		$access.off('change.umem').on('change.umem', function () {
			toggleLevels($edit, $(this).val());
		});
	};
})(jQuery);
