<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Resource;
use App\Models\Roadmap;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class LibraryController extends Controller
{
    public function index(Request $request): Response
    {
        $category = (string) $request->get('category', 'all');

        $query = Resource::with(['roadmap', 'topic', 'task', 'attachments']);

        if ($category !== 'all') {
            $query->where('category', $category);
        }

        $resources = $query->latest()->get();

        $roadmaps = Roadmap::with(['phases.topics'])->get();

        $categories = Resource::distinct()->pluck('category')->filter()->values();

        return Inertia::render('Library/Index', [
            'resources' => $resources,
            'roadmaps' => $roadmaps,
            'categories' => $categories,
            'currentCategory' => $category,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'resource_type' => ['required', 'in:url,note,text'],
            'category' => ['required', 'string', 'max:64'],
            'content_or_url' => ['required', 'string'],
            'roadmap_id' => ['nullable', 'exists:roadmaps,id'],
            'topic_id' => ['nullable', 'exists:topics,id'],
        ]);

        Resource::create($validated);

        return redirect()->back()->with('success', "Resource '{$validated['title']}' added to Study Library.");
    }

    public function destroy(string $id): RedirectResponse
    {
        $resource = Resource::findOrFail($id);
        $title = $resource->title;
        $resource->delete();

        return redirect()->back()->with('success', "Resource '{$title}' removed.");
    }
}
