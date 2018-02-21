<?php
namespace Plokko\LaravelFirebase\Traits;

/**
 * Trait SyncWithFirebase
 * apply this trait to a Model to add Firebase real-time database synchronization
 * @package Plokko\LaravelFirebase\Traits
 * @property string $firebaseReference
 */
trait SyncWithFirebase
{
    //protected $firebaseReference

    /**
     * Boot the trait and add the model events to synchronize with firebase
     */
    public static function bootSyncsWithFirebase()
    {
        static::created(function ($model) {
            $model->saveToFirebase('set');
        });
        static::updated(function ($model) {
            $model->saveToFirebase('update');
        });
        static::deleted(function ($model) {
            $model->saveToFirebase('delete');
        });
        if(in_array('Illuminate\Database\Eloquent\SoftDeletes', class_uses(self::class))){
            static::restored(function ($model) {
                $model->saveToFirebase('set');
            });
        }
    }


    /**
     * @param FirebaseInterface|null $firebaseClient
     */
    public function setFirebaseClient($firebaseClient)
    {
        $this->firebaseClient = $firebaseClient;
    }
    /**
     * @return array
     */
    protected function getFirebaseSyncData()
    {
        if ($fresh = $this->fresh()) {
            return $fresh->toArray();
        }
        return [];
    }
    /**
     * Manually sync to firebase
     */
    public function syncWithFirebase(){
        $this->saveToFirebase('update');
    }


    protected function getFirebaseReference(){

    }
    /**
     * Automatically casts Collection to SyncsWithFirebaseCollection
     * to allow bulk syncWithFirebase
     * @param array $models
     * @return SyncsWithFirebaseCollection
     */
    public function newCollection(array $models = [])
    {
        return new SyncsWithFirebaseCollection($models);
    }

    /**
     * @param $mode
     */
    protected function saveToFirebase($mode)
    {
        if (is_null($this->firebaseClient)) {
            $this->firebaseClient = new FirebaseLib(config('services.firebase.database_url'), config('services.firebase.secret'));
        }
        $path = $this->getTable() . '/' . $this->getKey();
        if ($mode === 'set') {
            $this->firebaseClient->set($path, $this->getFirebaseSyncData());
        } elseif ($mode === 'update') {
            $this->firebaseClient->update($path, $this->getFirebaseSyncData());
        } elseif ($mode === 'delete') {
            $this->firebaseClient->delete($path);
        }
    }
}