<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\{ Newsletter_Subscriber as Subscriber, Product };
use Illuminate\Support\Facades\{ Validator, File };
use App\Events\NewMail;
use PHPHtmlParser\StaticDom as DOM;


class SubscribersController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
      $validator =  Validator::make($request->all(),
                    [
                      'orderby' => ['regex:/^(email|updated_at)$/i', 'required_with:order'],
                      'order' => ['regex:/^(asc|desc)$/i', 'required_with:orderby']
                    ]);

      if($validator->fails()) abort(404);

      $base_uri = [];

      if($request->orderby)
      {
        $base_uri = ['orderby' => $request->orderby, 'order' => $request->order];
      }

      $subscribers = Subscriber::useIndex($request->orderby ?? 'primary')
                                ->select('id', 'email', 'updated_at')
                                ->orderBy($request->orderby ?? 'id', $request->order ?? 'desc')->paginate(15);

      $items_order = $request->order === 'desc' ? 'asc' : 'desc';

      return View('back.newsletters.subscribers', ['title' => 'Newsletter Subscribers',
                                                   'subscribers' => $subscribers,
                                                   'items_order' => $items_order,
                                                   'base_uri' => $base_uri]);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {      
      $emails = Subscriber::select('email')->where('email', '!=', '')->get()->pluck('email')->toArray();

      return View('back.newsletters.create', compact('emails'));
    }

    /**
     * Send a newsletter.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function send(Request $request)
    {
      $request->validate(['subject' => 'required', 'action' => 'required|in:render,send']);

      $mail_props = [
        'data' => [
          'html' => '',
          'selections' => []
        ],
        'view' => '',
        'to' => [],
        'subject' => $request->subject,
        'action' => $request->action
      ];

      if($request->newsletter)
      {
        $dom = DOM::loadStr($request->newsletter);
        
        $imgs = $dom->find('img');

        foreach($imgs as $img)
        {
          $data = [
                    'name' => $img->getAttribute('data-filename') ?? '',
                    'src' => $img->getAttribute('src')
                  ];

          if(!File::exists(public_path("storage/newsletter/{$data['name']}")))
          {
            list($extension, $file_content) = explode(',', $data['src']);

            $extension = str_ireplace(['data:image/', ';base64'], '', $extension);

            $extension = pathinfo($data['name'], PATHINFO_EXTENSION) ?? $extension ?? 'jpg';
            $filename  = urlencode(pathinfo($data['name'], PATHINFO_FILENAME));

            File::put(public_path("storage/newsletter/{$filename}.{$extension}"), base64_decode($file_content));
          }

          $img->setAttribute('src', secure_asset("storage/newsletter/{$data['name']}"));
        }

        $request->newsletter = $dom->outerHtml;
      }

      $selections = $request->selections;

      if($request->newsletter)
      {
        $mail_props['data']['html'] = $request->newsletter;
        $mail_props['view'] = 'mail.html';
      }
      else
      {
        $selections = array_combine($selections['titles'], $selections['ids']);

        if(!$selections = array_filter($selections))
        {
          return back()->withErrors(['newsletter' => __('The newsletter content is missing.')])->withInput();
        }

        $ids = explode(',', implode(',', array_values($selections)));

        $products = new \App\Http\Controllers\ProductsController;
        $products = $products->api($request, $ids);

        foreach($selections as &$selection)
        {
          $selection = $products->whereIn('id', explode(',', $selection))->toArray();
        }

        $mail_props['data']['selections'] = $selections;
        $mail_props['view'] = 'mail.newsletter';
      }

      if(!$emails = array_filter(explode(',', $request->emails)))
      {
        if($emails = Subscriber::select('email')->get())
          $emails = array_column($emails->toArray(), 'email');
      }
      else
      {
        foreach($emails as $key => &$email)
        {
          $email = trim($email);

          if(!filter_var($email, FILTER_VALIDATE_EMAIL))
            unset($emails[$key]);
        }
      }

      if(!$emails)
      {
        $validator = Validator::make($request->all(), [])->errors()->add('Emails', __('Invalid emails input'));

        return back()->withErrors($validator)->withInput();
      }

      $newsletter = File::get(resource_path('views/mail/newsletter.blade.php'));

      preg_match('/dir="(?P<direction>.*)"/iU', $newsletter, $matches_1);

      if(locale_direction() !== $matches_1['direction'])
      {
        preg_match_all('/style="(?P<styles>.*)"/iU', $newsletter, $matches_2);

        $flipped_css = [];

        foreach($matches_2['styles'] ?? [] as $k => $style)
        {
          $css = "{".htmlspecialchars_decode($style)."}";

          $parser = new \Sabberworm\CSS\Parser($css);
          $tree   = $parser->parse();

          $rtlcss = new \App\Libraries\RTLCSS($tree);
          $rtlcss->flip();

          if($new_css = $tree->render())
          {
            $flipped_css[$k] = trim(trim(trim($new_css), '{'), '}');
          }
        }

        $old_content = array_merge([wrap_str($matches_1['direction'], 'dir="', '"')], $matches_2['styles']);
        $new_content = array_merge([wrap_str(locale_direction(), 'dir="', '"')], $flipped_css);

        $newsletter = str_ireplace($old_content, $new_content, $newsletter);

        File::put(resource_path('views/mail/newsletter.blade.php'), $newsletter);
      }

      $mail_props['to'] = $emails;
      $mail_props['type'] = 'newsletter';

      delete_cached_view('mail/newsletter.blade.php');

      NewMail::dispatch($mail_props, config('mail.mailers.smtp.use_queue'));
            
      return back()->with(['newsletter_sent' => __('Newsletter sent successfully')]);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  string  $ids
     * @return \Illuminate\Http\Response
     */
    public function destroy(string $ids)
    {
        Subscriber::destroy(explode(',', $ids));

        return redirect()->route('subscribers');
    }
}
