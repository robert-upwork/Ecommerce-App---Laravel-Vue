<?php

namespace App\Models;

use Backpack\CRUD\CrudTrait;
use App\Models\Menu;
use Illuminate\Database\Eloquent\Model;
use Backpack\CRUD\ModelTraits\SpatieTranslatable\HasTranslations;

class MenuItem extends Model
{
    use CrudTrait;
    use HasTranslations;

    protected $table = 'menu_items';
    protected $fillable = ['name', 'type', 'link', 'page_id', 'parent_id', 'menu_id'];

    protected $translatable = ['name'];

    public function parent()
    {
        return $this->belongsTo('App\Models\MenuItem', 'parent_id');
    }
    public function children()
    {
        return $this->hasMany('App\Models\MenuItem', 'parent_id');
    }
    public function page()
    {
        return $this->belongsTo('App\Models\Page', 'page_id');
    }
    public function menu()
    {
        return $this->belongsTo('App\Models\Menu', 'menu_id', 'id');
    }

    /**
     * Get all menu items, in a hierarchical collection.
     * Only supports 2 levels of indentation.
     */
    public static function getTree($menuName = null)
    {
        $menu = self::orderBy('lft')->get();
        if(!is_null($menuName)) {
            $m = Menu::where('name', $menuName)->first();
            if($m) {
                $menuId = Menu::where('name', $menuName)->first()->id;
                $menu = self::orderBy('lft')->where('menu_id', $menuId)->get();
            }
        }
        if ($menu->count()) {
            foreach ($menu as $k => $menu_item) {
                $menu_item->children = collect([]);
                foreach ($menu as $i => $menu_subitem) {
                    if ($menu_subitem->parent_id == $menu_item->id) {
                        $menu_item->children->push($menu_subitem);
                        // remove the subitem for the first level
                        $menu = $menu->reject(function ($item) use ($menu_subitem) {
                            return $item->id == $menu_subitem->id;
                        });
                    }
                }
            }
        }

        return $menu;
    }
    public function url()
    {
        switch ($this->type) {
            case 'external_link':
                return $this->link;
                break;
            case 'internal_link':
                return is_null($this->link) ? '#' : u($this->link);
                break;
            default: //page_link
                return u($this->page->slug);
                break;
        }
    }
}