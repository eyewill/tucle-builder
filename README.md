### This package for laravel 5.3, [for 5.2 is here](https://github.com/eyewill/tucle-builder/tree/0.1.x).

## インストール <small>(install)</small>

<pre>
$ composer require eyewill/tucle-builder:dev-master
</pre>

... and append to config/app.php

<pre>
  'providers' => [
    ...
    Eyewill\TucleBuilder\TucleBuilderServiceProvider::class,
    ...
  ],
</pre>

## 使い方 <small>(How to use)</small>

### モジュールを作成

<pre>
$ php artisan make:module モジュール名
</pre>

モジュールに対応するテーブルを予め作成しておく必要があります

#### テーブルの例

<pre>
モジュール名 -> テーブル名
article -> articles
top_article -> top_articles
information -> information
</pre>

#### 引数

<pre>
# モジュール名 この名前でモジュールを作成する
$ php artisan make:module top_article
</pre>

#### オプション

<pre>
# --force 強制的に実行
$ php artisan make:module article --force

# --only 指定された要素のみ実行
# 指定可能要素 routes,model,presenter,views,requests
$ php artisan make:module article --only=views,presenter

# --table テーブル名を指定
# 変則的なテーブル名の場合に使用する
$ php artisan make:module article --table=system_article
</pre>
