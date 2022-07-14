# Easy Print

**This plugin loads invoice and payment records by descending date order and allows
1-click print of the pdf file. It solves that issue without having to download the PDF file
first, but getting it printed. The HTML version of receipt is ugly quality.**


# Comments from before
## Useful classes

### `App\Service\TemplateRenderer`

Very simple class to load a PHP template. When writing a PHP template be careful to use correct escaping function: `echo htmlspecialchars($string, ENT_QUOTES);`.

## Extending the plugin

Let's say you would want to improve this plugin to only export invoices that were not invoiced before. In that case you have two options:

- persist the IDs of already exported invoices to a file
- mark them as already exported with UCRM API using a [Custom Attribute](https://ucrm.docs.apiary.io/#reference/custom-attributes)
