var emps_tinymce_settings = {
	content_css: "/editor.css",
{{if $lang == "en"}}
{{else}}
	language_url : '/js/tinymce/langs/ru.js',
{{/if}}

	style_formats_merge: true,
	remove_script_host: false,

    style_formats: [

{{if $lang == 'en'}}
{title: "Website - blocks", items: [
    {title: 'Page Title', block: 'div', classes: "page-header", wrapper: true},
    {title: 'Red Background', block: 'p', classes: "alert alert-danger"},
    {title: 'Green Background', block: 'p', classes: "alert alert-success"},
    {title: 'Well', block: 'div', classes: "well"},
    {title: 'Shallow Well', block: 'div', classes: "well well-sm"}

]},

{title: "Website - inline", items: [
    {title: 'Small font', inline: 'small', classes: ""},
    {title: 'Big font', inline: 'span', classes: "bigger"},
]}

{{else}}
{title: "Сайт - блоки", items: [
    {title: 'Заголовок страницы', block: 'div', classes: "page-header", wrapper: true},
    {title: 'Красный фон', block: 'p', classes: "alert alert-danger"},
    {title: 'Зелёный фон', block: 'p', classes: "alert alert-success"},
    {title: 'Колодец', block: 'div', classes: "well"},
    {title: 'Мелкий колодец', block: 'div', classes: "well well-sm"}

]},

{title: "Сайт - строчные", items: [
    {title: 'Малый шрифт (small)', inline: 'small', classes: ""},
    {title: 'Крупный шрифт (class bigger)', inline: 'span', classes: "bigger"},
]}
{{/if}}

    ],
	
	convert_urls: false,
	relative_urls: false,
	document_base_url: "",	
	plugins : ["code image charmap paste anchor searchreplace visualblocks visualchars link","table emoticons textcolor"],
	paste_auto_cleanup_on_paste : true,
{{if $tinymce_short}}
	toolbar1: "undo redo | styleselect | bold italic | alignleft aligncenter alignright alignjustify | bullist numlist outdent indent "+
	"| link image charmap emoticons | code ",
{{else}}
	toolbar1: "undo redo | styleselect | bold italic | alignleft aligncenter alignright alignjustify | bullist numlist outdent indent "+
	"| link image charmap emoticons | code ",
	toolbar2: "forecolor backcolor | paste | table | blockquote",
{{/if}}
	image_advtab: true,
        {{if $lang == 'en'}}
table_class_list: [
    {title: 'No', value: ''},
    {title: 'Horizontal borders', value: 'table'},
    {title: 'Striped rows', value: 'table table-striped'},
    {title: 'Bordered', value: 'table table-bordered'}
],
    image_class_list: [
    {title: 'None', value: ''},
    {title: '100% width', value: 'pic-full'},
    {title: '1/4 → right', value: 'pic-3-right'},
    {title: '1/4 ← left', value: 'pic-3-left'},
    {title: '1/3 → right', value: 'pic-4-right'},
    {title: '1/3 ← left', value: 'pic-4-left'},
    {title: '1/2 → right', value: 'pic-6-right'},
    {title: '1/2 ← left', value: 'pic-6-left'},
    {title: '1/2 center', value: 'pic-6-center'}
],

    {{else}}
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

{{/if}}

	image_dimensions: false
 };