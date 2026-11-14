<?php

namespace App\Http\Controllers\Admin;

use App\CatalogImage;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ServiceRequest;
use App\Models\Service;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class ServiceController extends Controller
{
    public function index(): View
    {
        return view('admin.services.index', ['services' => Service::ordered()->paginate(15)]);
    }

    public function create(): View
    {
        return view('admin.services.form', [
            'service' => new Service(['is_active' => true, 'sort_order' => 0]),

        ]);
    }

    public function store(ServiceRequest $request): RedirectResponse
    {
        $service = new Service;
        CatalogImage::save($service, $request->safe()->except(['image', 'remove_image']), $request->file('image'), $request->boolean('remove_image'));

        return to_route('admin.services.edit', $service)->with('status', 'Hizmet oluşturuldu.');
    }

    public function edit(Service $service): View
    {
        return view('admin.services.form', ['service' => $service]);
    }

    public function update(ServiceRequest $request, Service $service): RedirectResponse
    {
        CatalogImage::save($service, $request->safe()->except(['image', 'remove_image']), $request->file('image'), $request->boolean('remove_image'));

        return to_route('admin.services.edit', $service)->with('status', 'Hizmet güncellendi.');
    }

    public function confirmDelete(Service $service): View
    {
        return view('admin.confirm-delete', [
            'entry' => $service, 'entryName' => $service->title, 'type' => 'Hizmet', 'blocked' => false,
            'destroyRoute' => route('admin.services.destroy', $service), 'cancelRoute' => route('admin.services.index'),
        ]);
    }

    public function destroy(Service $service): RedirectResponse
    {
        $image = $service->image;
        $service->delete();
        CatalogImage::deleteIfUnused($image);

        return to_route('admin.services.index')->with('status', 'Hizmet silindi.');
    }
}
