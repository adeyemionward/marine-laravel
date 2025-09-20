<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\KnowledgeBaseCategory;
use App\Models\KnowledgeBaseDocument;
use App\Models\User;

class KnowledgeBaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get the first admin user (or create one for testing)
        $admin = User::where('email', 'admin@marine.ng')->first();
        if (!$admin) {
            $admin = User::create([
                'name' => 'System Admin',
                'email' => 'admin@marine.ng',
                'password' => bcrypt('password'),
                'email_verified_at' => now(),
            ]);
        }

        // Create categories
        $categories = [
            [
                'name' => 'Getting Started',
                'icon' => '🚀',
                'color' => '#10B981',
                'description' => 'Everything you need to know to get started with Marine.ng',
                'sort_order' => 1,
            ],
            [
                'name' => 'Equipment Listings',
                'icon' => '⚙️',
                'color' => '#3B82F6',
                'description' => 'How to create and manage your equipment listings',
                'sort_order' => 2,
            ],
            [
                'name' => 'User Account',
                'icon' => '👤',
                'color' => '#8B5CF6',
                'description' => 'Managing your user account and profile',
                'sort_order' => 3,
            ],
            [
                'name' => 'Seller Guide',
                'icon' => '🏪',
                'color' => '#F59E0B',
                'description' => 'Complete guide for sellers on Marine.ng',
                'sort_order' => 4,
            ],
            [
                'name' => 'FAQs',
                'icon' => '❓',
                'color' => '#EF4444',
                'description' => 'Frequently asked questions',
                'sort_order' => 5,
            ],
        ];

        foreach ($categories as $categoryData) {
            $category = KnowledgeBaseCategory::create([
                ...$categoryData,
                'created_by' => $admin->id,
            ]);

            // Create documents for each category
            $this->createDocumentsForCategory($category, $admin);
        }
    }

    private function createDocumentsForCategory($category, $admin)
    {
        $documentsData = [
            'Getting Started' => [
                [
                    'title' => 'Welcome to Marine.ng',
                    'summary' => 'Get started with Marine.ng - Nigeria\'s premier marine equipment marketplace',
                    'content' => "# Welcome to Marine.ng\n\nMarine.ng is Nigeria's leading online marketplace for marine equipment, connecting buyers and sellers of quality marine products.\n\n## What you can do on Marine.ng\n\n- **Browse Equipment**: Explore thousands of marine equipment listings\n- **List Your Equipment**: Sell your marine equipment to a wide audience\n- **Connect with Sellers**: Communicate directly with equipment owners\n- **Secure Transactions**: Safe and secure payment processing\n\n## Getting Started\n\n1. **Create an Account**: Sign up for a free Marine.ng account\n2. **Complete Your Profile**: Add your details and verification\n3. **Start Browsing**: Explore equipment categories\n4. **Make Contact**: Reach out to sellers for inquiries\n\nWelcome aboard!",
                    'document_type' => 'guide',
                    'tags' => ['welcome', 'getting-started', 'overview'],
                    'is_featured' => true,
                ],
                [
                    'title' => 'Creating Your First Account',
                    'summary' => 'Step-by-step guide to creating your Marine.ng account',
                    'content' => "# Creating Your Marine.ng Account\n\nFollow these simple steps to join the Marine.ng community:\n\n## Step 1: Registration\n\n1. Visit the Marine.ng registration page\n2. Enter your email address\n3. Choose a strong password\n4. Provide your full name\n5. Click 'Create Account'\n\n## Step 2: Email Verification\n\n1. Check your email inbox\n2. Click the verification link\n3. Your account is now active\n\n## Step 3: Complete Your Profile\n\n1. Add your phone number\n2. Set your location\n3. Upload a profile picture\n4. Add a brief bio\n\n## Next Steps\n\n- Explore equipment categories\n- Set up your seller profile (optional)\n- Start browsing listings",
                    'document_type' => 'tutorial',
                    'tags' => ['account', 'registration', 'setup'],
                ],
            ],
            'Equipment Listings' => [
                [
                    'title' => 'How to Create Equipment Listings',
                    'summary' => 'Complete guide to creating effective equipment listings',
                    'content' => "# Creating Equipment Listings\n\nLearn how to create compelling equipment listings that attract buyers.\n\n## Before You Start\n\n- Gather all equipment details\n- Take high-quality photos\n- Determine fair pricing\n- Prepare equipment documentation\n\n## Creating Your Listing\n\n### 1. Basic Information\n- Equipment name and model\n- Manufacturer details\n- Year of manufacture\n- Current condition\n\n### 2. Detailed Description\n- Key features and specifications\n- Usage history\n- Maintenance records\n- Any issues or repairs\n\n### 3. Pricing and Location\n- Set competitive pricing\n- Choose payment options\n- Set pickup location\n- Delivery options (if available)\n\n### 4. Photos and Documentation\n- Upload multiple high-quality photos\n- Include documentation (manuals, receipts)\n- Add videos if helpful\n\n## Tips for Success\n\n- Be honest about condition\n- Respond quickly to inquiries\n- Keep listings updated\n- Use relevant keywords",
                    'document_type' => 'guide',
                    'tags' => ['listings', 'selling', 'equipment'],
                    'is_featured' => true,
                ],
            ],
            'User Account' => [
                [
                    'title' => 'Managing Your Profile',
                    'summary' => 'How to update and manage your Marine.ng profile',
                    'content' => "# Managing Your Profile\n\nKeep your Marine.ng profile up-to-date to build trust with other users.\n\n## Profile Sections\n\n### Personal Information\n- Full name\n- Email address\n- Phone number\n- Location\n\n### Profile Picture\n- Upload a clear, professional photo\n- Shows your face clearly\n- Good lighting and quality\n\n### Bio and Description\n- Brief description about yourself\n- Your experience with marine equipment\n- What you're looking for\n\n### Verification\n- Email verification (required)\n- Phone verification (recommended)\n- Identity verification (for sellers)\n\n## Privacy Settings\n\n- Control who can see your information\n- Manage notification preferences\n- Set communication preferences\n\n## Account Security\n\n- Use a strong, unique password\n- Enable two-factor authentication\n- Regular security checkups\n- Log out from shared devices",
                    'document_type' => 'guide',
                    'tags' => ['profile', 'account', 'security'],
                ],
            ],
            'Seller Guide' => [
                [
                    'title' => 'Becoming a Verified Seller',
                    'summary' => 'Steps to become a verified seller on Marine.ng',
                    'content' => "# Becoming a Verified Seller\n\nBecome a trusted seller on Marine.ng and reach more customers.\n\n## Benefits of Verification\n\n- **Trust Badge**: Display verification badge\n- **Higher Visibility**: Featured in search results\n- **Better Conversion**: Buyers prefer verified sellers\n- **Advanced Features**: Access to seller tools\n\n## Verification Requirements\n\n### Business Information\n- Business registration documents\n- Tax identification number\n- Business address verification\n- Contact information\n\n### Financial Information\n- Bank account details\n- Payment processing setup\n- Tax compliance documents\n\n### Identity Verification\n- Government-issued ID\n- Proof of address\n- Business licenses (if applicable)\n\n## Verification Process\n\n1. **Application Submission**\n   - Complete seller application\n   - Upload required documents\n   - Pay verification fee (if applicable)\n\n2. **Document Review**\n   - Review period: 3-5 business days\n   - Additional information may be requested\n   - Status updates via email\n\n3. **Approval and Setup**\n   - Account approval notification\n   - Setup seller dashboard\n   - Begin listing equipment\n\n## Maintaining Verification\n\n- Keep information updated\n- Maintain good seller ratings\n- Follow Marine.ng policies\n- Regular compliance checks",
                    'document_type' => 'guide',
                    'tags' => ['seller', 'verification', 'business'],
                    'is_featured' => true,
                ],
            ],
            'FAQs' => [
                [
                    'title' => 'Frequently Asked Questions',
                    'summary' => 'Common questions and answers about Marine.ng',
                    'content' => "# Frequently Asked Questions\n\n## General Questions\n\n### What is Marine.ng?\nMarine.ng is Nigeria's premier online marketplace for marine equipment, connecting buyers and sellers across the country.\n\n### Is Marine.ng free to use?\nBrowsing and basic account features are free. Premium features and seller verification may have associated fees.\n\n### How do I contact customer support?\nYou can reach our support team through the contact form, email, or phone during business hours.\n\n## Account Questions\n\n### How do I reset my password?\n1. Go to the login page\n2. Click 'Forgot Password'\n3. Enter your email address\n4. Check your email for reset instructions\n\n### Can I have multiple accounts?\nNo, each user should maintain only one account. Multiple accounts may result in suspension.\n\n### How do I delete my account?\nContact customer support to request account deletion. Note that this action is irreversible.\n\n## Buying Questions\n\n### How do I make a purchase?\n1. Find the equipment you want\n2. Contact the seller through our messaging system\n3. Negotiate terms and arrange payment\n4. Complete the transaction\n\n### Is my payment secure?\nYes, we use industry-standard security measures to protect your payment information.\n\n### What if I have issues with a seller?\nContact our support team immediately. We'll help mediate and resolve the issue.\n\n## Selling Questions\n\n### How much does it cost to sell?\nBasic listings are free. Premium features and verification may have fees.\n\n### How do I get paid?\nPayments are processed through your chosen payment method after successful transactions.\n\n### Can I edit my listings?\nYes, you can edit your listings at any time through your seller dashboard.",
                    'document_type' => 'faq',
                    'tags' => ['faq', 'help', 'common-questions'],
                    'is_featured' => true,
                ],
            ],
        ];

        $categoryDocuments = $documentsData[$category->name] ?? [];

        foreach ($categoryDocuments as $docData) {
            KnowledgeBaseDocument::create([
                ...$docData,
                'category_id' => $category->id,
                'created_by' => $admin->id,
                'status' => 'published',
                'published_at' => now(),
            ]);
        }
    }
}
