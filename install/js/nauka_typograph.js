BX.addCustomEvent('OnEditorInitedBefore', function() {
	var _this = this;
	this.AddButton({
		iconClassName: 'bxhtmled-button-nauka_typograph', // класс кнопки
		src: '/bitrix/images/nauka.typograph/nauka_typograph.png', // путь к иконке
		id: 'nauka_typograph', // id кнопки
		title: 'Типограф текста', // title кнопки
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
						};
					}
				});
			};
		}
	});
});