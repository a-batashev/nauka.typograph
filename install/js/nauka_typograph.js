BX.addCustomEvent('OnEditorInitedBefore', function() {
	var _this = this;
	this.AddButton({
		src: '/bitrix/images/nauka.typograph/nauka_typograph.png', // icon
		id: 'nauka_typograph', // button id
		name: BX.message('NAUKA_TYPOGRAPH_BUTTON_TITLE'), // button title
		handler: function() {
			var text = _this.GetContent();
			if (text.length > 0) {
				BX.ajax({
					method: 'POST',
					url: '/bitrix/tools/nauka.typograph/nauka_typograph.php',
					dataType: 'html',
					data: { 'text': text },
					onsuccess: function(response) {
						if (response.length > 0) {
							_this.SetContent(response, true);
						}
					}
				});
			}
		}
	});
});