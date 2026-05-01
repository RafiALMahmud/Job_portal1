<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Job;
use App\Models\JobType;
use App\Models\User;
use Illuminate\Database\Seeder;
use RuntimeException;

class JobSeeder extends Seeder
{
    public function run(): void
    {
        $categories = Category::query()->pluck('id', 'name');
        $jobTypes = JobType::query()->pluck('id', 'name');

        $employerEmails = collect($this->jobs())
            ->pluck('employer_email')
            ->unique()
            ->values();

        $employers = $employerEmails->mapWithKeys(function (string $email) {
            return [$email => User::query()
                ->where('email_lookup_hash', User::emailLookupHash($email))
                ->first()];
        });

        $missingEmployers = $employers->filter(fn ($user) => $user === null)->keys()->all();
        if ($missingEmployers !== []) {
            throw new RuntimeException(
                'JobSeeder requires EmployerSeeder to run first. Missing employer users: '
                . implode(', ', $missingEmployers)
            );
        }

        Job::query()
            ->whereIn('user_id', $employers->pluck('id')->all())
            ->delete();

        foreach ($this->jobs() as $jobData) {
            $categoryId = $categories[$jobData['category']] ?? null;
            $jobTypeId = $jobTypes[$jobData['job_type']] ?? null;

            if ($categoryId === null || $jobTypeId === null) {
                throw new RuntimeException(
                    sprintf(
                        'Missing category/job type for seeded job "%s" (%s / %s).',
                        $jobData['title'],
                        $jobData['category'],
                        $jobData['job_type']
                    )
                );
            }

            $employer = $employers[$jobData['employer_email']];

            Job::create([
                'title' => $jobData['title'],
                'category_id' => $categoryId,
                'job_type_id' => $jobTypeId,
                'user_id' => $employer->id,
                'vacancy' => $jobData['vacancy'],
                'salary' => $jobData['salary'],
                'location' => $jobData['location'],
                'description' => $jobData['description'],
                'benefits' => $jobData['benefits'],
                'responsibility' => $jobData['responsibility'],
                'qualifications' => $jobData['qualifications'],
                'keywords' => $jobData['keywords'],
                'experience' => $jobData['experience'],
                'company_name' => $jobData['company_name'],
                'company_location' => $jobData['company_location'],
                'company_website' => $jobData['company_website'],
                'status' => 1,
                'isFeatured' => $jobData['is_featured'],
            ]);
        }
    }

    private function jobs(): array
    {
        return [
            [
                'employer_email' => 'careers@northstardigital.example.com',
                'title' => 'Senior Laravel Engineer',
                'category' => 'Information Technology',
                'job_type' => 'Full Time',
                'vacancy' => 2,
                'salary' => '145000',
                'location' => 'Gulshan 2, Dhaka',
                'description' => 'Lead backend delivery for a multi-tenant SaaS platform used by growing operations teams across South Asia.',
                'benefits' => 'Hybrid work, yearly bonus, private health coverage, learning budget',
                'responsibility' => 'Design Laravel services, mentor engineers, review architecture, and ship secure API features.',
                'qualifications' => 'Strong Laravel and MySQL fundamentals, 5+ years of production PHP experience, and solid API design skills.',
                'keywords' => 'Laravel, PHP, MySQL, REST API, Redis',
                'experience' => '5',
                'company_name' => 'Northstar Digital',
                'company_location' => 'Gulshan 2, Dhaka',
                'company_website' => 'https://northstardigital.example.com',
                'is_featured' => 1,
            ],
            [
                'employer_email' => 'careers@northstardigital.example.com',
                'title' => 'Frontend Engineer',
                'category' => 'Information Technology',
                'job_type' => 'Remote',
                'vacancy' => 3,
                'salary' => '110000',
                'location' => 'Remote from Bangladesh',
                'description' => 'Build polished customer-facing dashboards with a product team that cares about accessibility and performance.',
                'benefits' => 'Remote-first schedule, design pairing, device allowance',
                'responsibility' => 'Implement responsive interfaces, collaborate with backend engineers, and improve UI quality.',
                'qualifications' => '2+ years with modern JavaScript frameworks and strong HTML/CSS fundamentals.',
                'keywords' => 'JavaScript, Vue, React, CSS, Accessibility',
                'experience' => '2',
                'company_name' => 'Northstar Digital',
                'company_location' => 'Gulshan 2, Dhaka',
                'company_website' => 'https://northstardigital.example.com',
                'is_featured' => 1,
            ],
            [
                'employer_email' => 'careers@northstardigital.example.com',
                'title' => 'DevOps Engineer',
                'category' => 'Information Technology',
                'job_type' => 'Full Time',
                'vacancy' => 1,
                'salary' => '155000',
                'location' => 'Banani, Dhaka',
                'description' => 'Own deployment pipelines and observability for customer-facing products with strict uptime requirements.',
                'benefits' => 'On-call stipend, training budget, health insurance',
                'responsibility' => 'Manage CI/CD, infrastructure automation, logging, and incident readiness.',
                'qualifications' => 'Experience with AWS, Docker, Linux, and production release workflows.',
                'keywords' => 'AWS, Docker, Kubernetes, CI/CD, Terraform',
                'experience' => '4',
                'company_name' => 'Northstar Digital',
                'company_location' => 'Banani, Dhaka',
                'company_website' => 'https://northstardigital.example.com',
                'is_featured' => 0,
            ],
            [
                'employer_email' => 'hiring@finverse.example.com',
                'title' => 'Data Analyst',
                'category' => 'Finance',
                'job_type' => 'Full Time',
                'vacancy' => 2,
                'salary' => '95000',
                'location' => 'Banani, Dhaka',
                'description' => 'Turn payment and lending data into clear operational insights for finance and risk teams.',
                'benefits' => 'Performance bonus, lunch support, certification reimbursement',
                'responsibility' => 'Build dashboards, investigate trends, and partner with business teams on reporting.',
                'qualifications' => 'Strong SQL, spreadsheet fluency, and a comfort with business storytelling.',
                'keywords' => 'SQL, Power BI, Excel, Analytics, Reporting',
                'experience' => '3',
                'company_name' => 'Finverse Analytics',
                'company_location' => 'Banani, Dhaka',
                'company_website' => 'https://finverse.example.com',
                'is_featured' => 0,
            ],
            [
                'employer_email' => 'hiring@finverse.example.com',
                'title' => 'Product Manager',
                'category' => 'Finance',
                'job_type' => 'Full Time',
                'vacancy' => 1,
                'salary' => '170000',
                'location' => 'Gulshan 1, Dhaka',
                'description' => 'Drive roadmap decisions for a secure fintech platform used by lenders, operations teams, and end users.',
                'benefits' => 'Quarterly bonus, health insurance, stock appreciation plan',
                'responsibility' => 'Define priorities, align stakeholders, and ship product improvements with design and engineering.',
                'qualifications' => '5+ years in product management with strong execution and communication habits.',
                'keywords' => 'Product Management, Agile, Fintech, Roadmap, Discovery',
                'experience' => '5',
                'company_name' => 'Finverse Analytics',
                'company_location' => 'Gulshan 1, Dhaka',
                'company_website' => 'https://finverse.example.com',
                'is_featured' => 1,
            ],
            [
                'employer_email' => 'hiring@finverse.example.com',
                'title' => 'Customer Success Associate',
                'category' => 'Customer Service',
                'job_type' => 'Full Time',
                'vacancy' => 2,
                'salary' => '55000',
                'location' => 'Banani, Dhaka',
                'description' => 'Support business customers as they onboard to a finance operations platform.',
                'benefits' => 'Medical support, incentive plan, structured training',
                'responsibility' => 'Resolve support tickets, coordinate onboarding, and surface product feedback.',
                'qualifications' => 'Strong written communication and a calm, customer-first approach.',
                'keywords' => 'Customer Success, Support, CRM, Onboarding',
                'experience' => '1',
                'company_name' => 'Finverse Analytics',
                'company_location' => 'Banani, Dhaka',
                'company_website' => 'https://finverse.example.com',
                'is_featured' => 0,
            ],
            [
                'employer_email' => 'talent@pixelcraft.example.com',
                'title' => 'UI/UX Designer',
                'category' => 'Design',
                'job_type' => 'Full Time',
                'vacancy' => 2,
                'salary' => '90000',
                'location' => 'Dhanmondi, Dhaka',
                'description' => 'Craft interfaces, wireframes, and design systems for high-growth startup clients.',
                'benefits' => 'Creative budget, hybrid work, team retreats',
                'responsibility' => 'Own product flows, prototype ideas, and collaborate closely with frontend engineers.',
                'qualifications' => 'Strong portfolio, thoughtful UX reasoning, and comfort in Figma.',
                'keywords' => 'UI, UX, Figma, Design Systems, Prototyping',
                'experience' => '2',
                'company_name' => 'Pixelcraft Studio',
                'company_location' => 'Dhanmondi, Dhaka',
                'company_website' => 'https://pixelcraft.example.com',
                'is_featured' => 1,
            ],
            [
                'employer_email' => 'talent@pixelcraft.example.com',
                'title' => 'Content Strategist',
                'category' => 'Marketing',
                'job_type' => 'Part Time',
                'vacancy' => 1,
                'salary' => '45000',
                'location' => 'Remote from Dhaka',
                'description' => 'Shape messaging systems and campaign content for startup launches and product growth initiatives.',
                'benefits' => 'Flexible hours, remote setup support',
                'responsibility' => 'Plan campaign copy, content calendars, and landing page messaging.',
                'qualifications' => 'Strong writing samples and experience with digital content planning.',
                'keywords' => 'Content, Copywriting, SEO, Campaigns, Brand',
                'experience' => '2',
                'company_name' => 'Pixelcraft Studio',
                'company_location' => 'Remote from Dhaka',
                'company_website' => 'https://pixelcraft.example.com',
                'is_featured' => 0,
            ],
            [
                'employer_email' => 'talent@pixelcraft.example.com',
                'title' => 'Brand Designer',
                'category' => 'Design',
                'job_type' => 'Contract',
                'vacancy' => 1,
                'salary' => '80000',
                'location' => 'Dhanmondi, Dhaka',
                'description' => 'Work on identity systems, campaign visuals, and design direction for emerging brands.',
                'benefits' => 'Flexible contract, creative autonomy',
                'responsibility' => 'Develop brand systems, presentation decks, and campaign visuals.',
                'qualifications' => 'Strong visual craft across typography, composition, and presentation design.',
                'keywords' => 'Branding, Illustrator, Visual Design, Identity',
                'experience' => '3',
                'company_name' => 'Pixelcraft Studio',
                'company_location' => 'Dhanmondi, Dhaka',
                'company_website' => 'https://pixelcraft.example.com',
                'is_featured' => 0,
            ],
            [
                'employer_email' => 'jobs@healthbridge.example.com',
                'title' => 'Operations Executive',
                'category' => 'Healthcare',
                'job_type' => 'Full Time',
                'vacancy' => 2,
                'salary' => '60000',
                'location' => 'Uttara, Dhaka',
                'description' => 'Coordinate clinic partner operations and ensure day-to-day service quality across care teams.',
                'benefits' => 'Health coverage, transport allowance, festival bonus',
                'responsibility' => 'Track operational issues, support clinics, and maintain partner documentation.',
                'qualifications' => 'Strong coordination skills and comfort working with cross-functional stakeholders.',
                'keywords' => 'Operations, Healthcare, Coordination, Support',
                'experience' => '2',
                'company_name' => 'HealthBridge',
                'company_location' => 'Uttara, Dhaka',
                'company_website' => 'https://healthbridge.example.com',
                'is_featured' => 0,
            ],
            [
                'employer_email' => 'jobs@healthbridge.example.com',
                'title' => 'QA Engineer',
                'category' => 'Information Technology',
                'job_type' => 'Full Time',
                'vacancy' => 1,
                'salary' => '85000',
                'location' => 'Uttara, Dhaka',
                'description' => 'Help maintain high quality across healthcare workflows, dashboards, and partner integrations.',
                'benefits' => 'Health insurance, learning support, structured QA mentorship',
                'responsibility' => 'Write test plans, execute regression checks, and strengthen release quality.',
                'qualifications' => 'Hands-on QA experience with web apps and strong attention to detail.',
                'keywords' => 'QA, Testing, Automation, Selenium, Regression',
                'experience' => '3',
                'company_name' => 'HealthBridge',
                'company_location' => 'Uttara, Dhaka',
                'company_website' => 'https://healthbridge.example.com',
                'is_featured' => 0,
            ],
            [
                'employer_email' => 'jobs@healthbridge.example.com',
                'title' => 'HR Associate',
                'category' => 'Human Resources',
                'job_type' => 'Full Time',
                'vacancy' => 1,
                'salary' => '50000',
                'location' => 'Uttara, Dhaka',
                'description' => 'Support recruiting coordination, onboarding, and people operations for a growing health-tech team.',
                'benefits' => 'Festival bonus, team lunch, health support',
                'responsibility' => 'Coordinate interviews, onboarding, and internal people processes.',
                'qualifications' => 'Good communication skills, strong organization, and a people-oriented mindset.',
                'keywords' => 'HR, Recruitment, Onboarding, Coordination',
                'experience' => '1',
                'company_name' => 'HealthBridge',
                'company_location' => 'Uttara, Dhaka',
                'company_website' => 'https://healthbridge.example.com',
                'is_featured' => 0,
            ],
        ];
    }
}
