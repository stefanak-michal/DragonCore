# Views and elements

## Template system

None! Why to add more complexity and slowness? You can write html and insert php pieces into it. It's fast and it works without the need to learn something new.

## View & Layout

To render vies and layouts you have to use `\Dragon\View` class. It has dual usage:
- Singleton - it's the main instance for controller->method logic `\Dragon\View::gi()`
- Instantiation - when you need to render any view `new \Dragono\View()`

In the description below is used _`<View>`_ because of two different options to access instance of this class.

Default directory for view, layout and element files is `views`. You can change it in config through key `viewsDirectory`.

Automatically view file is loaded like this:  
`<root>/views/<controller>/<method>.phtml`

If you want to set custom view, you can set "view" this way:  
`<View>->view('hp/test');`  

It will load a file from:  
`<root>/views/hp/test.phtml`

You can use "absolute" path from project root, if you start with slash:  
`<View>->view('/something/foo');` 

Then result will be:  
`<root>/something/foo.phtml`

Layouts files works the same way.

To get html result from `View` call method render.  
`<view>->render()`

You have a option to not use view or layout with set null. If view is a null, layout is automatically ignored.  
```
<View>->view(null);
<View>->layout(null);
```

## Elements

To render a element you need to instantiate class View.

Example:  
`echo (new \core\View('elements/bar'))->render();`

Rendered element will be  
`<root>/views/elements/bar.phtml`  

`View` constructor also accepts variables and layout.

## Variables

Everything setted with `<View>->set();` (for single value) and `<View>->vars();` (for multiple values) is passed to layout and view.  

## After render

Class `\Dragon\View` contains public static property `$afterRender` where you can assign callable which will be called everytime view or layout is rendered. Callable is invoked with two arguments, path to rendered file and rendered content. Your callable should return modified content.
