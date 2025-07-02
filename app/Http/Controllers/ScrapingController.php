<?php

namespace App\Http\Controllers;

use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Models\ScrapingSource;
use App\Models\ScrapedArticle;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class ScrapingController extends Controller
{
    public function index(Request $request)
    {
        $sort = $request->query('sort', 'desc'); // デフォルトは新しい順
        $sourceId = $request->query('source');

        // URL一覧取得（セレクトボックス用）
        $sources = ScrapingSource::all();

        // 絞り込み条件
        $query = ScrapedArticle::with('source')->orderBy('published_at', $sort);
        if ($sourceId) {
            $query->where('scraping_source_id', $sourceId);
        }

        $articles = $query->paginate(20)->appends([
            'sort' => $sort,
            'source' => $sourceId,
        ]);

        return view('scraping.index', compact('articles', 'sort', 'sourceId', 'sources'));
    }

    public function sources()
    {
        $sources = ScrapingSource::with(['articles' => function ($query) {
            $query->orderByDesc('created_at');
        }])->get();
        return view('scraping.sources', compact('sources'));
    }

    public function storeSource(Request $request)
    {
        $request->validate(['url' => 'required|url']);
        $source = ScrapingSource::create([
            'name' => $request->name,
            'url' => $request->url
        ]);
        return redirect()->route('scraping.sources')->with('success', 'URLを登録しました');
    }

    public function destroy($id)
    {
        $source = ScrapingSource::findOrFail($id);
        $source->delete(); // 紐づく記事はマイグレーションで cascade 指定済みであれば同時に削除

        return redirect()->route('scraping.sources')->with('success', 'URLを削除しました');
    }

    public function scrape($id, $internal = false)
    {
        $source = ScrapingSource::findOrFail($id);
        $baseUrl = $source->url;
        $baseParsed = parse_url($baseUrl);
        $baseHost = $baseParsed['scheme'] . '://' . $baseParsed['host'];

        // list-0.html → list-0_1.html に対応
        $basePrefix = preg_replace('/-\d+\.html$/', '', $baseUrl);
        $page = 0;

        while (true) {
            $url = $basePrefix . '-' . $page . '.html';

            Log::info("Scraping page: {$url}");

            $html = @file_get_contents($url);
            if (!$html) {
                Log::warning("ページが見つからないか取得できません: {$url}");
                break;
            }

            $doc = new \DOMDocument();
            @$doc->loadHTML($html);
            $xpath = new \DOMXPath($doc);

            $links = $xpath->query("//div[contains(@class, 'c-card')]//a");

            if ($links->length === 0) {
                Log::info("記事リンクが見つかりませんでした: {$url}");
                break;
            }

            foreach ($links as $link) {
                if ($link instanceof \DOMElement) {
                    $href = $link->getAttribute("href");
                    $title = trim($link->textContent);

                    if (!$href || Str::startsWith($href, ['javascript:', '#'])) {
                        continue;
                    }

                    if (!Str::startsWith($href, ['http://', 'https://'])) {
                        $href = $baseHost . '/' . ltrim($href, '/');
                    }

                    $timeNode = $link->getElementsByTagName("time")->item(0);
                    $publishedAt = null;

                    if ($timeNode) {
                        $rawDate = trim($timeNode->nodeValue);
                        try {
                            $publishedAt = \Carbon\Carbon::createFromFormat('Y-m-d', $rawDate);
                        } catch (\Exception $e) {
                            $publishedAt = null;
                        }
                    }

                    ScrapedArticle::firstOrCreate([
                        'url' => $href,
                    ], [
                        'scraping_source_id' => $source->id,
                        'title' => $title,
                        'published_at' => $publishedAt,
                    ]);
                }
            }

            $page++;
        }

        // 外部からの呼び出し時のみリダイレクトする
        if (!$internal) {
            return redirect()->route('scraping.index')->with('success', '全ページのスクレイピングが完了しました。');
        }
    }

    public function scrapeAll()
    {
        $sources = ScrapingSource::all();

        foreach ($sources as $source) {
            $this->scrape($source->id, true); // 第2引数で内部呼び出しフラグを渡す
        }

        return redirect()->route('scraping.index')->with('success', '全URLのスクレイピングが完了しました。');
    }
}
