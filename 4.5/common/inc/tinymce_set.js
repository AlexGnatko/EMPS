var emps_tinymce_settings = {
	content_css: "/editor.css",
	language_url : '/js/tinymce/langs/ru.js',

	style_formats_merge: true,

    style_formats: [
		{title: "Сайт - блоки", items: [
	        {title: 'Заголовок страницы', block: 'div', classes: "page-header", wrapper: true},
	        {title: 'Отбивка справа', block: 'div', classes: "ip-insert", wrapper: true},
	        {title: 'Красный фон', block: 'p', classes: "alert alert-danger"},
	        {title: 'Зелёный фон', block: 'p', classes: "alert alert-success"},
	        {title: 'Колодец', block: 'div', classes: "well"},
	        {title: 'Мелкий колодец', block: 'div', classes: "well well-sm"}
			
		]},

		{title: "Сайт - строчные", items: [
	        {title: 'Малый шрифт (small)', inline: 'small', classes: ""},
	        {title: 'Крупный шрифт (class bigger)', inline: 'span', classes: "bigger"},			
		]},
	
		{title: "Картинки", items: [
	        {title: 'Подпись для картинки', block: 'p', classes: "pic-descr"}
		]}
    ],
	
	convert_urls: true,
	relative_urls: false,
	document_base_url: "",	
	plugins : ["code image charmap paste anchor searchreplace visualblocks visualchars link","table emoticons textcolor"],
{{if $tinymce_short}}
	toolbar1: "undo redo | styleselect | bold italic | alignleft aligncenter alignright alignjustify | bullist numlist outdent indent "+
	"| link image charmap emoticons | code ",
{{else}}
	toolbar1: "undo redo | styleselect | bold italic | alignleft aligncenter alignright alignjustify | bullist numlist outdent indent "+
	"| link image charmap emoticons | code ",
	toolbar2: "forecolor backcolor | paste | table",
{{/if}}
	image_advtab: true,
    table_class_list: [
        {title: 'Нет', value: ''},
        {title: 'Горизонтальные границы', value: 'table'},
        {title: 'Чередование', value: 'table table-striped'},		
        {title: 'Клетки', value: 'table table-bordered'}
    ],
    image_class_list: [
        {title: 'Нет', value: ''},
        {title: '100% ширины', value: 'pic-full'},
        {title: '1/4 → cправа', value: 'pic-3-right'},
        {title: '1/4 ← слева', value: 'pic-3-left'},
        {title: '1/3 → cправа', value: 'pic-4-right'},
        {title: '1/3 ← слева', value: 'pic-4-left'},
        {title: '1/2 → cправа', value: 'pic-6-right'},
        {title: '1/2 ← слева', value: 'pic-6-left'},
        {title: '1/2 по центру', value: 'pic-6-center'}
    ],
	
	image_dimensions: false
 };