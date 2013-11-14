## 使用帮助 ##

这是一个 Markdown 语法补充插件，支持 div、table、abbr、dl 等等 HTML 标签的输入，更多具体语法格式，请参考：  
<http://michelf.ca/projects/php-markdown/extra/>

## 语法说明 ##

### PHP Markdown Extra 内部 HTML ###

Markdown下可以插入HTML代码。当某些特性Markdown不支持而HTML容易实现时，这就很有用了。但是Markdown对块级元素有严格的限制。来看Markdown语法文档：

 > HTML块级元素，比如 div, table, pre, p 等必须与其它内容用空行分隔开，并且开始与结束标签不能有缩进。
 
PHP Markdown Extra放宽了这种限制：

 1. 块级元素的开始标签不能有超过三个空格的缩进，超过的按代码块处理。
 2. 列表中块级元素的缩进与列表项的缩进一致。（更多的缩进也没问题，只要第一个开始标签不是缩进了太多而当成了代码块，见第一条）。

### HTML 块级元素内 Markdown ###

原生的 Markdown 不支持块级元素内 Markdown 语法。PHP Markdown Extra 则支持，只要为元素添加一个特性 `markdown="1"`。例如：

    <DIV markdown="1">
    This is *true* markdown text.
    </DIV>

在转换时 `markdown="1"` 特性去掉，div内的 markdown 转换为 HTML。最终结果为：

    <div>
    <p>this is <em>true</em> markdown text.</p>
    </div>

表格单元可以包含内联与块级元素，像上面的例子将转换为内联元素。如果想转换为块级元素，则添加上特性 `markdown="block"` 而不是 `markdown="1"`。

### 标题 Id 特性 ###

PHP Markdown Extra 下可以给标题元素添加id特性。id写在标题那行末，以#打头并用大括号括起来，像这样：

    Header 1            {#header1}
    ========
    
    ## Header 2 ##      {#header2}

然后给文档相应位置添加锚点：

    [Link back to header 1](#header1)

目前只能给标题元素添加id特性。

### 栅栏式代码块 ###

PHP Markdown Extra v1.2 引进了不需要缩进的代码块语法，栅栏式代码块。像Markdown的代码块一样，只是不用缩进，而由起始与末尾的栅栏行去界定代码块。代码块的起始行由三个或更多的~组成，末尾行由相同数目的~组成。例如：

    This is a paragraph introducing:
    
    ~~~~~~~~~~~~~~~~~~~~~
    a one-line code block
    ~~~~~~~~~~~~~~~~~~~~~

与缩进式代码块相比，栅栏式代码块中代码开头与结尾可以有空白行：

    ~~~
    
    blank line before
    blank line after
    
    ~~~

缩进式代码块不能紧跟在列表后面，因为这时列表的缩进语法起作用。栅栏式代码块没这个限制：

    1.  List item
    
        Not an indented code block, but a second paragraph
        in the list item
    
    ~~~~
    This is a code block, fenced-style
    ~~~~

当编辑器不能缩进文本块时栅栏式代码块就很有用，比如浏览器中的文本框。

### 表格 ###

PHP Markdown Extra支持创建简单的表格。“简单”是指这样的：

    First Header  | Second Header
    ------------- | -------------
    Content Cell  | Content Cell
    Content Cell  | Content Cell

第一行是表头；第二行是分隔行，将表头与下面内容分开；再下面的每一行都是表格中的一行。列之间由管道符(|)分开。转换结果：

    <table>
      <thead>
        <tr>
          <th>first header</th>
          <th>second header</th>
        </tr>
      </thead>
      <tbody>
        <tr>
          <td>content cell</td>
          <td>content cell</td>
        </tr>
        <tr>
          <td>content cell</td>
          <td>content cell</td>
        </tr>
      </tbody>
    </table>

也可以在行首与行末加上管道符。两种方式随你喜欢，结果是一样的：

    | First Header  | Second Header |
    | ------------- | ------------- |
    | Content Cell  | Content Cell  |
    | Content Cell  | Content Cell  |

注意每行至少得有一个管道符。假如创建单列表格，则在每行行首或行末，或两处都加上管道符。  
可以在分隔行里加上冒号来指定列的对齐方式。冒号在左边则列左对齐，在右边则列右对齐，在两边则列中间对齐。

    | Item      | Value |
    | --------- | -----:|
    | Computer  | $1600 |
    | Phone     |   $12 |
    | Pipe      |    $1 |

转换时相关列会加上align属性。  
单元格内可以用Markdown生成内联元素：

    | Function name | Description                    |
    | ------------- | ------------------------------ |
    | `help()`      | Display the help window.       |
    | `destroy()`   | **Destroy your computer!**     |

### 定义列表 ###

PHP Markdown Extra支持定义列表。定义列表很像词典，由词汇及定义组成。  
简单的定义列表由单行的词汇，加上以冒号打头的定义组成：

    Apple
    :   Pomaceous fruit of plants of the genus Malus in 
        the family Rosaceae.
    
    Orange
    :   The fruit of an evergreen tree of the genus Citrus.

词汇与上条定义之间需要用空行分开。定义可能是多行的，理应缩进，但是犯懒不缩进也行：

    Apple
    :   Pomaceous fruit of plants of the genus Malus in 
    the family Rosaceae.
    
    Orange
    :   The fruit of an evergreen tree of the genus Citrus.

缩进与不缩进得到的结果一样：

    <dl>
      <dt>apple</dt>
      <dd>pomaceous fruit of plants of the genus malus in 
      the family rosaceae.</dd>
      <dt>orange</dt>
      <dd>the fruit of an evergreen tree of the genus citrus.</dd>
    </dl>

像普通列表一样，定义可以包含多个段落，以及其它的块级元素，比如引用，代码块，列表，甚至是其它的定义列表。

    Term 1
    
    :   This is a definition with two paragraphs. Lorem ipsum 
        dolor sit amet, consectetuer adipiscing elit. Aliquam 
        hendrerit mi posuere lectus.
    
        Vestibulum enim wisi, viverra nec, fringilla in, laoreet
        vitae, risus.
    
    :   Second definition for term 1, also wrapped in a paragraph
        because of the blank line preceding it.
    
    Term 2
    
    :   This definition has a code block, a blockquote and a list.
    
            code block.

        > block quote
        > on two lines.
    
        1.  first list item
        2.  second list item

### 脚注 ###

脚注的实现跟参考链接的实现差不多。脚注由两部分组成：上标数字；脚注文本。一个脚注例子：

    That's some text with a footnote.[^1]
    
    [^1]: And that's the footnote.

脚注文本可以放在文档中的任意位置，但是转换时是按照加注处位置的先后生成列表，然后放到文档末尾。注意一个脚注不能添加到两个地方，否则第二个加注处视为普通文本。

每个脚注必须有一个唯一的名字，这个名字用来链接加注处与脚注文本，跟脚注编号没有关系。取名规则与HTML中的Id特性一样（例子中名字为1,但是id命名必须以字母开头，译注）。

脚注可以包含块级元素，这意味着可以将多个段落，列表，引用等放在脚注里。跟普通列表一样在定义中用四个空格缩进：

    That's some text with a footnote.[^1]
    
    [^1]: And that's the footnote.
        That's the second paragraph.

如果想排版得更好，可以这样：

    [^1]:
        And that's the footnote.
    
        That's the second paragraph.

### 输出 ###

脚注只是一种输出不能满足所有人，将来的版本可能支持不同的输出。现在的输出与Daring Fireball上的示例一样，不过作了稍微改动。下面是上面第一个例子的输出：

    <p>that's some text with a footnote.<sup id="fnref:1"><a href="#fn:1" rel="footnote">1</a></sup></p>
    <div class="footnotes">
      <hr />
      <ol>
        <li id="fn:1">
          <p>and that's the footnote.<a href="#fnref:1" rev="footnote">&#8617;</a></p>
        </li>
      </ol>
    </div>

链接的rel与rev特性表明了元素之间的关系，加注处链接到脚注（因此rel=”footnote”)，脚注反链到加注处（因此rev=”footnote”）。可以用css给链接添加样式：

    a[rel="footnote"]
    a[rev="footnote"]

也可以给这些链接添加class与title特性。在PHP Markdown Extra文件的开头有四项与此相关的设置。特性值中的%%会被替换为当前脚注编号。例如：

    define('MARKDOWN_FN_LINK_TITLE', "Go to footnote %%.");

### 缩写 ###

PHP Markdown Extra支持缩写（HTML标签<abbr>）。语法很简单，像这样定义缩写：

    *[HTML]: Hyper Text Markup Language
    *[W3C]:  World Wide Web Consortium

然后在文档的其它地方，像这样使用缩写：

    The HTML specification
    is maintained by the W3C.

转换后的结果：

    The <abbr title="Hyper Text Markup Language">HTML</abbr> specification
    is maintained by the <abbr title="World Wide Web Consortium">W3C</abbr>.

缩写区分大小写，多个单词也无妨。缩写也可能是空的定义，这种情况下，仍然生成<abbr>标签，但是没有title特性。

    Operation Tigra Genesis is going well.
    
    *[Tigra Genesis]:

缩写定义可以放在文档的任意位置，转换时会去掉这些定义。

### 强调 ###

PHP Markdown Extra稍微改变了Markdown的强调语法。下划线语法只对整个单词有效，单词中间的下划线视为正常字符。如果想强调单词中的部分内容，可以使用星号语法。

比如：

    Please open the folder "secret_magic_box".

下划线位于单词中间，不会转换，得到的HTML为：

    <P>Please open the folder "secret_magic_box".</P>

下划线语法仍然可用，只要将整个单词包住：

    I like it when you say _you love me_.

strong的强调语法一样：单词中间不能用下划线语法，只能用星号语法。

### 反义 ###

    PHP Markdown Extra可用反斜杠\来反义冒号(:)与管道符(|)，防止被判断为定义列表或表格。
