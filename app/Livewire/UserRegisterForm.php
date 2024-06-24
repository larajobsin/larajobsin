<?php

namespace App\Livewire;

use App\Models\User;
use Filament\Forms\Components\Actions;
use Filament\Forms\Components\Actions\Action;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\Group;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Support\Enums\VerticalAlignment;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class UserRegisterForm extends Component implements HasForms
{
    use InteractsWithForms;

    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill();
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make()->heading('Create an Account')->schema([
                    TextInput::make('name')->required(),
                    TextInput::make('email')->required()->email()->unique(User::class, 'email'),
                    TextInput::make('password')->required()->password(),
                    TextInput::make('password_confirmation')->required()->password(),
                    Select::make('role')->label('Register As')
                        ->options([
                            'candidate' => 'Candidate',
                            'employer' => 'Employer',
                        ]),
                    Group::make()->schema([
                        Checkbox::make('terms')->label('I agree')->required(),
                        Actions::make([
                            Action::make('Login')->url(route('login'))->color('success')->link(),
                        ])->alignEnd(),
                    ])->columns(2),
                ]),
            ])
            ->statePath('data')
            ->model(User::class);
    }

    public function create()
    {
        $data = $this->form->getState();

        $record = User::create($data);
        $record->assignRole($data['role']);
        Notification::make()->success()->title('Account created.')->send();

        return to_route('login');

        // $this->form->model($record)->saveRelationships();
    }

    public function render(): View
    {
        return view('livewire.user-register-form');
    }
}
