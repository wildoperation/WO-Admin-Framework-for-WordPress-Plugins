jQuery(document).ready(function ($) {
	$(".wowpads-repeater").each(function () {
		const $repeater = $(this);
		const $tbody = $repeater.find("tbody");
		const $add = $repeater.find(".wometa-repeater-controls--add");
		const $remove = $repeater.find(".wometa-repeater-controls--remove");

		$tbody.sortable();

		if (wometa_repeater.has_sort_handle !== "no") {
			$tbody.sortable("option", "handle", wometa_repeater.sort_handle_selector);
		}

		function resetRowValues($row) {
			$row.find("input, select").val("");
			$row.find("textarea").text("");
			$tbody.sortable("refresh");
		}

		$add.on("click", function (e) {
			e.preventDefault();

			const $row = $(this).closest(".wometa-repeater-row");
			const $newRow = $row.clone(true).insertAfter($row);

			resetRowValues($newRow);
		});

		$remove.on("click", function (e) {
			e.preventDefault();

			const $row = $(this).closest(".wometa-repeater-row");

			if ($repeater.find(".wometa-repeater-row").length === 1) {
				resetRowValues($row);
			} else {
				$row.remove();
			}
		});
	});
});
