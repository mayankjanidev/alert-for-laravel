<?php

namespace Mayank\Alert;

use Illuminate\Support\Facades\Session;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Model;

class Alert
{
	protected string $title;

	protected ?string $description;

	protected string $type = 'success';

	protected ?string $action = null;

	protected array $meta = [];

	protected ?string $entity = null;

	protected array $langParameters = [];

	public const DEFAULT_TITLE = 'Alert Message';

	private function __construct(string $type)
	{
		$this->type = $type;
	}

	public static function custom(string $alertType): static
	{
		return new static($alertType);
	}

	public static function info(): static
	{
		return new static(AlertType::info->value);
	}

	public static function success(): static
	{
		return new static(AlertType::success->value);
	}

	public static function warning(): static
	{
		return new static(AlertType::warning->value);
	}

	public static function failure(): static
	{
		return new static(AlertType::failure->value);
	}

	public static function model(Model $model, array $langParameters = []): static
	{
		$alert = static::success();

		$modelName = class_basename($model);
		$entity = Str::snake($modelName);
		$alert->entity = trans()->has("alert::messages.$entity") ? $entity : 'model';

		if ($model->wasRecentlyCreated) {
			$alert->action = 'created';
		} else if (!$model->exists) {
			$alert->action = 'deleted';
		} else {
			$alert->action = 'updated';
		}

		$alert->langParameters = $langParameters;
		$alert->langParameters['model_name'] = $modelName;

		return $alert;
	}

	public static function for(string $entity, array $langParameters = []): static
	{
		$alert = static::success();

		$alert->entity = Str::snake($entity);
		$alert->action = 'updated';
		$alert->langParameters = $langParameters;

		return $alert;
	}

	public static function current(): ?static
	{
		$sessionAlert = Session::get('alert');

		if ($sessionAlert == null)
			return null;

		$alert = static::custom($sessionAlert['type']);
		$alert->title = $sessionAlert['title'];
		$alert->description = $sessionAlert['description'];
		$alert->action = $sessionAlert['action'];
		$alert->meta = $sessionAlert['meta'];

		return $alert;
	}

	public static function exists(): bool
	{
		return Session::has('alert');
	}

	public function title(string $title): self
	{
		$this->title = $title;
		return $this;
	}

	public function description(string $description): self
	{
		$this->description = $description;
		return $this;
	}

	public function type(string $type): self
	{
		$this->type = $type;
		return $this;
	}

	public function action(string $action): self
	{
		$this->action = $action;
		return $this;
	}

	public function meta(array $meta): self
	{
		$this->meta = $meta;
		return $this;
	}

	public function getTitle(): string
	{
		return $this->title;
	}

	public function getDescription(): ?string
	{
		return $this->description;
	}

	public function getType(): string
	{
		return $this->type;
	}

	public function getAction(): ?string
	{
		return $this->action;
	}

	public function getMeta(): array
	{
		return $this->meta;
	}

	protected function setAlertMessageIfNotProvided(): void
	{
		if (!isset($this->title)) {
			if ($this->entity != null) {
				$this->title = trans("alert::messages.$this->entity.$this->action.title", $this->langParameters);
			} else {
				$this->title = self::DEFAULT_TITLE;
			}
		}

		if (!isset($this->description)) {
			if ($this->entity != null) {
				$langKey = "alert::messages.$this->entity.$this->action.description";
				$this->description = trans()->has($langKey) ? trans($langKey, $this->langParameters) : null;
			} else {
				$this->description = null;
			}
		}
	}

	public function flash(): void
	{
		$this->setAlertMessageIfNotProvided();

		Session::flash('alert', array('title' => $this->title, 'description' => $this->description, 'type' => $this->type, 'action' => $this->action, 'meta' => $this->meta));
	}
}
