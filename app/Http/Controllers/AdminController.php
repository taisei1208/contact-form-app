<?php

namespace App\Http\Controllers;

use App\Http\Requests\IndexContactRequest;
use App\Models\Category;
use App\Models\Contact;
use App\Models\Tag;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AdminController extends Controller
{
    public function index(IndexContactRequest $request)
    {
        $categories = Category::all();

        $validated = $request->validated();

        $contactsFilters = $this->getContactFilters($validated);

        $contacts = $this->getContactQuery($contactsFilters)
            ->paginate(7)
            ->appends($request->query());

        $tags = Tag::all();

        return view('admin.index', compact('categories', 'contacts', 'tags'));
    }

    public function export(IndexContactRequest $request): StreamedResponse
    {
        $validated = $request->validated();

        $contactFilters = $this->getContactFilters($validated);

        $contacts = $this->getContactQuery($contactFilters)
            ->get();

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="contacts_'.now()->format('Ymd').'.csv"',
        ];

        $callback = function () use ($contacts) {
            $handle = fopen('php://output', 'w');

            fwrite($handle, "\xEF\xBB\xBF");

            fputcsv($handle, [
                'ID',
                'お名前',
                '性別',
                'メールアドレス',
                '電話番号',
                '住所',
                '建物名',
                'お問い合わせの種類',
                'お問い合わせ内容',
                '作成日時',
            ]);

            foreach ($contacts as $contact) {
                fputcsv($handle, [
                    $contact->id,
                    $contact->last_name.' '.$contact->first_name,
                    $contact->gender_label,
                    $contact->email,
                    $contact->tel,
                    $contact->address,
                    $contact->building,
                    $contact->category?->content,
                    $contact->detail,
                    $contact->created_at->format('Y-m-d H:i:s'),
                ]);
            }

            fclose($handle);

        };

        return response()->stream($callback, 200, $headers);

    }

    public function show(Contact $contact)
    {
        $contact->with(['category', 'tags']);

        return view('admin.show', compact('contact'));
    }

    public function destroy(Contact $contact)
    {
        $contact->delete();

        return redirect()->route('admin.index');
    }

    private function getContactFilters(array $validated): array
    {
        return [
            'keyword' => $validated['keyword'] ?? null,
            'gender' => $validated['gender'] ?? null,
            'category_id' => $validated['category_id'] ?? null,
            'date' => $validated['date'] ?? null,
        ];
    }

    private function getContactQuery(array $filters)
    {
        return Contact::with(['category', 'tags'])
            ->search($filters)
            ->orderBy('created_at', 'desc');
    }
}
