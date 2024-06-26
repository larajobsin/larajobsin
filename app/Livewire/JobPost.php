<?php

namespace App\Livewire;

use App\Models\Company;
use App\Models\Job;
use App\Models\Tag;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Forms;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Group;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Notifications\Notification;
use Filament\Support\Enums\MaxWidth;
use Filament\Support\RawJs;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class JobPost extends Component implements HasForms
{
    use InteractsWithActions;
    use InteractsWithForms;

    public ?array $data = [];

    public function mount()
    {
        $companyCount = Company::where('user_id', auth()->id())->count();
        if ($companyCount == 0) {
            Notification::make()->title('Create Company')->body('You don\'t have a company.')->info()->send();

            return to_route('create.company');
        }
        $this->form->fill();
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make()->heading('Post Job')->schema([
                    TextInput::make('title')
                        ->required()
                        ->helperText('Example: "Senior Laravel Developer", "Software Engineer"')
                        ->live(onBlur: true),
                    ColorPicker::make('hilight_color')
                        ->helperText('Pick a color that best represents your job')
                        ->live(onBlur: true),
                    Select::make('employment_type')
                        ->options([
                            'full_time' => 'Full Time',
                            'part_time' => 'Part Time',
                            'contract' => 'Contract',
                        ])
                        ->live(onBlur: true),
                    Checkbox::make('manage_by_self')->live()->helperText('Check if you want to handle application on your own')->label('I want to handle application on my own'),
                    TextInput::make('apply_url')->visible(function (Get $get) {
                        if (! empty($get('manage_by_self'))) {
                            return true;
                        }

                        return false;
                    })->columnSpanFull()
                        ->required()
                        ->helperText('https://yourcompany.com/careers'),
                ])->columns(2),
                Section::make()->schema([
                    Group::make()->schema([
                        RichEditor::make('job_description')->requiredIf('manage_by_self', function (Get $get) {
                            if (! empty($get('manage_by_self'))) {
                                return false;
                            }

                            return true;
                        })->helperText('Add Job Description'),
                        RichEditor::make('job_requirement')->helperText('Add Job Requirement'),
                    ])->columns(2),
                    Group::make()->schema([
                        RichEditor::make('job_benefits')->helperText('Add Job Benefits'),
                        RichEditor::make('qualification')->helperText('Add required qualification for the Job.'),
                    ])->columns(2),
                    Group::make()->schema([
                        RichEditor::make('experience')->helperText('Add required Experience for the Job.'),
                        FileUpload::make('job_brochure')
                            ->acceptedFileTypes(['application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'])
                            ->helperText('Only pdf, doc and docx files are accepted'),
                    ])->columns(2),

                ])->visible(function (Get $get) {
                    if (! empty($get('manage_by_self'))) {
                        return false;
                    }

                    return true;
                }),
                Section::make()->heading('Salary Range')->schema([
                    TextInput::make('salary_from')->prefixIcon('forkawesome-inr')
                        ->mask(RawJs::make('$money($input)'))
                        ->stripCharacters(',')
                        ->numeric()
                        ->helperText('Salary range should be in INR')
                        ->live(onBlur: true),
                    TextInput::make('salary_to')->prefixIcon('forkawesome-inr')
                        ->mask(RawJs::make('$money($input)'))
                        ->stripCharacters(',')
                        ->numeric()
                        ->helperText('Salary range should be in INR')
                        ->live(onBlur: true),
                ])->columns(2),
                Section::make()->heading('Location')->schema([
                    TextInput::make('location')
                        ->required()
                        ->helperText('Example: "Remote", "Remote / USA", "New York City", "Remote GMT-5", etc.')
                        ->live(onBlur: true),
                ]),
                Section::make()->schema([
                    TagsInput::make('tags')
                        ->suggestions(Tag::query()->pluck('name')->all())
                        ->separator(',')
                        ->required()
                        ->tagPrefix('#')
                        ->rules(['max:5'])
                        ->helperText('Max 5 tags are allowed.')
                        ->live(onBlur: true),
                ]),
                Forms\Components\Actions::make([
                    Forms\Components\Actions\Action::make('Preview')->modalContent(function (Get $get) {
                        $companyLogo = Company::where('user_id', auth()->id())->first()->getFirstMediaUrl('company-logo');
                        $company = Company::where('user_id', auth()->id())->first();
                        $companyDetail['logo'] = $companyLogo;
                        $companyDetail['detail'] = $company;

                        return view('components.job-preview', [
                            'data' => $get,
                            'company' => $companyDetail,
                        ]);
                    })->modalHeading('Preview')
                        ->modalSubmitAction(false)
                        ->modalCancelAction(false)
                        ->slideOver()
                        ->modalWidth(MaxWidth::SevenExtraLarge),
                ]),
            ])

            ->statePath('data')
            ->model(Job::class);
    }

    public function create()
    {

        $data = $this->form->getState();
        // dd($data);
        $data['user_id'] = auth()->id();
        $data['company_id'] = Company::where('user_id', auth()->id())->first()->id;
        $record = Job::create($data);
        $this->form->model($record)->saveRelationships();
        Notification::make()->title('Job Post Created')->success()->send();

        return to_route('home');
    }

    public function render(): View
    {
        return view('livewire.job-post');
    }
}
