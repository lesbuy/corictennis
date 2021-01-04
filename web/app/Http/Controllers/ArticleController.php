<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Article;

class ArticleController extends Controller
{
    //
		public function show() {
				$articles = Article::all();
				foreach ($articles as $article) {
						echo $article->title . "<br>";
				}
		}

		public function read($id) {
				$article = Article::find($id);
				echo $article->title . "<br>";
		}
}
