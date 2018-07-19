<?php

namespace App\Http\Controllers;

use App\Author;
use App\Events\QuoteCreated;
use App\Quote;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Event;

class QuoteController extends Controller
{
    public function getIndex($author = null)
    {
        if(!is_null($author)){
            $quote_author = Author::where('name' , $author)->first();
            if($quote_author){
                $quotes = $quote_author->quotes()->orderBy('created_at' , 'desc')->paginate(6);
            }
        }else{
            $quotes = Quote::orderBy('created_at' , 'desc')->paginate(6);
        }

        return view('index',['quotes' => $quotes]);
    }

    public function postQuote(Request $request)
    {
        $this->validate($request,[
            'author' => 'required|max:60|alpha',
            'quote' => 'required|max:500',
            'email' => 'required|email'

        ]);

        // Check if author and quote text are provided (Validation is explained in advanced part)
        if (!isset($request['author']) || !isset($request['quote']) || strlen($request['author']) === 0 || strlen($request['quote']) === 0) {
            return redirect()->back()->with(['fail' => 'Please provide both an author and a quote!']);
        }
        $authorText = ucfirst($request['author']);
        $quoteText = $request['quote'];

        // Check if author already exists in db
        $author = Author::where('name', $authorText)->first();
        if (!$author) {
            $author = new Author();
            $author->name = $authorText;
            $author->email = $request['email'];
            $author->save();
        }

        // Create quote and map to author
        $quote = new Quote();
        $quote->quote = $quoteText;
        $author->quotes()->save($quote);

        Event::fire(new QuoteCreated($author));

        return redirect()->route('index')->with(['success' => 'Quote saved!']);
    }

    public function getDeleteQuote($quote_id)
    {
        $quote = Quote::find($quote_id);
        $author_deleted = false;
//        $quote = Quote::where('id',$quote_id)->first();
        if(count($quote->author->quotes) === 1){
            $quote->author->delete();
            $author_deleted = true;
        }


        $quote->delete();

        $msg = $author_deleted ? 'Quote and Author deleted' : 'Quote deleted!';
        return redirect()->route('index')->with([
            'success' => $msg
        ]);
    }
}