<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\AutomatedTemplateType;
use App\Enums\CommunicationCode;
use App\Models\AutomatedTemplate;
use App\Models\CommunicationStatus;
use Illuminate\Database\Seeder;

class TemplateSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $templatesArray = $this->emailTemplatesArray();

        $templates = collect($templatesArray)
            ->map(function (array $template): array {
                unset($template['code']);

                return [
                    ...$template,
                    'user_id' => 1,
                    'enabled' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            })
            ->toArray();

        AutomatedTemplate::query()->insert($templates);

        $tempSMSAutomatedTemplate = AutomatedTemplate::factory()
            ->create([
                'name' => 'TEMP SMS',
                'subject' => null,
                'type' => AutomatedTemplateType::SMS,
                'user_id' => 1,
                'enabled' => true,
            ]);

        collect($templatesArray)
            ->each(function (array $template) use ($tempSMSAutomatedTemplate): void {
                $emailAutomatedTemplate = AutomatedTemplate::query()
                    ->where('type', AutomatedTemplateType::EMAIL)
                    ->where('name', $template['name'])
                    ->first();

                CommunicationStatus::query()
                    ->where('code', $template['code'])
                    ->update([
                        'automated_email_template_id' => $emailAutomatedTemplate->id,
                        'automated_sms_template_id' => $tempSMSAutomatedTemplate->id,
                    ]);
            });

        $tempEmailAutomatedTemplate = AutomatedTemplate::factory()
            ->create([
                'name' => 'TEMP EMAIL',
                'type' => AutomatedTemplateType::EMAIL,
                'user_id' => 1,
                'enabled' => true,
            ]);

        CommunicationStatus::query()
            ->whereNull('automated_email_template_id')
            ->whereNull('automated_sms_template_id')
            ->update([
                'automated_email_template_id' => $tempEmailAutomatedTemplate->id,
                'automated_sms_template_id' => $tempSMSAutomatedTemplate->id,
            ]);
    }

    private function emailTemplatesArray(): array
    {
        return [
            [
                'code' => CommunicationCode::WELCOME->value,
                'name' => 'W-1  (EMAIL) WECOME TEMPLATE',
                'subject' => 'W-1 (EMAIL) WELCOME TEMPLATE TEST -  [Original Name] uploaded your account into your YouNegotiate Portal Account.',
                'type' => 'email',
                'content' => '&lt;p&gt;Test Team: Your log in to manage your past due accounts!!&lt;/p&gt;&lt;p&gt;[Last Name]&lt;/p&gt;&lt;p&gt;[Birth Date]&lt;/p&gt;&lt;p&gt;[Last 4 SSN]&lt;/p&gt;&lt;p&gt;&lt;br&gt;&lt;/p&gt;&lt;p&gt;You have 3-5 past due accounts and a free YouNegotiate portal account to respond in any way you would in real life.&lt;/p&gt;&lt;p&gt;We are not live for payments so please make payments using the &lt;u&gt;Credit Card 4111-1111-1111-1111 a&lt;/u&gt;ny dates and address!&lt;/p&gt;&lt;p&gt;&lt;br&gt;&lt;/p&gt;&lt;p&gt;View and Accept Offers&lt;/p&gt;&lt;p&gt;Create and send an Offer you can afford - negotiate&lt;/p&gt;&lt;p&gt;Report not paying/Dispute&lt;/p&gt;&lt;p&gt;Self-Manage your payment plans to support real life circumstances.&lt;/p&gt;&lt;p&gt;&lt;br&gt;&lt;/p&gt;&lt;p&gt;Check out the Coming Soon list on your top right profile&lt;/p&gt;&lt;p&gt;&lt;br&gt;&lt;/p&gt;&lt;p&gt;&lt;br&gt;&lt;/p&gt;&lt;p&gt;&lt;span style=&quot;color: inherit;&quot;&gt;Hi Donna,&lt;/span&gt;&lt;/p&gt;&lt;p&gt;&lt;br&gt;&lt;/p&gt;&lt;p&gt;&lt;span style=&quot;color: inherit;&quot;&gt;Debt Free Americans has partnered with YouNegotiate to create a free portal account for you to manage your past due balances. Our mission is to help you stop collections and the risk of wage garnishments, bank levies and loss of tax refunds.&lt;/span&gt;&lt;/p&gt;&lt;p&gt;&lt;br&gt;&lt;/p&gt;&lt;p&gt;&lt;span style=&quot;color: inherit;&quot;&gt;This email is showing to belong to this account:&lt;/span&gt;&lt;/p&gt;&lt;p&gt;&lt;strong style=&quot;color: inherit;&quot;&gt;Account Name: &lt;/strong&gt;[Original Name]&lt;/p&gt;&lt;p&gt;&lt;strong style=&quot;color: inherit;&quot;&gt;Account Number:&lt;/strong&gt;&lt;span style=&quot;color: inherit;&quot;&gt; &lt;/span&gt;[Original Account Number]&lt;/p&gt;&lt;p&gt;&lt;strong&gt;Log into your Portal Account to View:&lt;/strong&gt; [You Negotiate Link]&lt;/p&gt;&lt;p&gt;&lt;strong&gt;DFA Tax Deductible Helping Hand Link: Y&lt;/strong&gt;ou can create to ask someone to pay your negotiated settlement balance or once your payment plan is set up to make payments on your behalf.&lt;/p&gt;&lt;p&gt;&lt;br&gt;&lt;/p&gt;&lt;p&gt;&lt;u style=&quot;color: inherit;&quot;&gt;Getting to Know Your Portal Account&lt;/u&gt;&lt;/p&gt;&lt;p&gt;&lt;br&gt;&lt;/p&gt;&lt;p&gt;&lt;span style=&quot;color: inherit;&quot;&gt;Any and all accounts matching your name, last four of social and date of birth will be delivered into your portal account as creditors send.&lt;/span&gt;&lt;/p&gt;&lt;p&gt;&lt;br&gt;&lt;/p&gt;&lt;p&gt;&lt;span style=&quot;color: inherit;&quot;&gt;In your portal account:&lt;/span&gt;&lt;/p&gt;&lt;ol&gt;&lt;li data-list=&quot;ordered&quot;&gt;&lt;span class=&quot;ql-ui&quot; contenteditable=&quot;false&quot;&gt;&lt;/span&gt;View and respond to creditor offers / see where your account is placed&lt;/li&gt;&lt;li data-list=&quot;ordered&quot;&gt;&lt;span class=&quot;ql-ui&quot; contenteditable=&quot;false&quot;&gt;&lt;/span&gt;Create offers and negotiate&lt;/li&gt;&lt;li data-list=&quot;ordered&quot;&gt;&lt;span class=&quot;ql-ui&quot; contenteditable=&quot;false&quot;&gt;&lt;/span&gt;Dispute and Report not paying&lt;/li&gt;&lt;li data-list=&quot;ordered&quot;&gt;&lt;span class=&quot;ql-ui&quot; contenteditable=&quot;false&quot;&gt;&lt;/span&gt;Self manage your payment plans&lt;/li&gt;&lt;li data-list=&quot;ordered&quot;&gt;&lt;span class=&quot;ql-ui&quot; contenteditable=&quot;false&quot;&gt;&lt;/span&gt;A tax deductible Debt Free Americans helping hand link you can use to ask someone for the gift of paying your agreed full settlement balance and on active payment plans to make payments of any amount.&lt;/li&gt;&lt;/ol&gt;&lt;p&gt;&lt;br&gt;&lt;/p&gt;&lt;p&gt;&lt;span style=&quot;color: inherit;&quot;&gt;This email is associated with an account uploaded by &lt;/span&gt;&lt;/p&gt;&lt;p&gt;&lt;br&gt;&lt;/p&gt;&lt;p&gt;&lt;span style=&quot;color: inherit;&quot;&gt;You can log into your free portal account with your last name, last 4 of social and Date of Birth.&lt;/span&gt;&lt;/p&gt;&lt;p&gt;&lt;br&gt;&lt;/p&gt;&lt;p&gt;&lt;span style=&quot;color: inherit;&quot;&gt;If this or another account matches your information, you&rsquo;ll gain access to your offers.&lt;/span&gt;&lt;/p&gt;&lt;p&gt;&lt;br&gt;&lt;/p&gt;&lt;p&gt;&lt;span style=&quot;color: inherit;&quot;&gt;If there isn&rsquo;t a match; that means the creditor sent a mobile or email that&rsquo;s not yours.&lt;/span&gt;&lt;/p&gt;&lt;p&gt;&lt;br&gt;&lt;/p&gt;&lt;p&gt;&lt;span style=&quot;color: inherit;&quot;&gt;Please help us save on communication costs to support giving consumers a free and safe portal by opting out of this email if it&rsquo;s not associated with Wells Fargo account🙏&lt;/span&gt;&lt;/p&gt;&lt;p&gt;&lt;br&gt;&lt;/p&gt;&lt;p&gt;&lt;span style=&quot;color: inherit;&quot;&gt;If you recognize this account and unable to log in, please email the 24/7 live team at help@younegotiate.com for help.&lt;/span&gt;&lt;/p&gt;&lt;p&gt;&lt;br&gt;&lt;/p&gt;&lt;p&gt;&lt;strong style=&quot;color: inherit;&quot;&gt;YouNegotiate URL: &lt;/strong&gt;&lt;span style=&quot;color: inherit;&quot;&gt;www.younegotiate.com&lt;/span&gt;&lt;/p&gt;&lt;p&gt;&lt;br&gt;&lt;/p&gt;&lt;p&gt;&lt;strong style=&quot;color: inherit;&quot;&gt;Deb Free Americans URL:&lt;/strong&gt;&lt;span style=&quot;color: inherit;&quot;&gt; www.debtfreeamericans.com&lt;/span&gt;&lt;/p&gt;&lt;p&gt;&lt;br&gt;&lt;/p&gt;&lt;p&gt;&lt;span style=&quot;color: inherit;&quot;&gt;We love ❤️ you and here to help you thrive with your creditors! You&rsquo;re not alone anymore so don&rsquo;t need to spend any money on debt negotiators or file bankruptcy.&lt;/span&gt;&lt;/p&gt;&lt;p&gt;&lt;br&gt;&lt;/p&gt;&lt;p&gt;&lt;span style=&quot;color: inherit;&quot;&gt;We can do this together❤️&lt;/span&gt;&lt;/p&gt;&lt;p&gt;&lt;br&gt;&lt;/p&gt;&lt;p&gt;&lt;span style=&quot;color: inherit;&quot;&gt;Sincerely,&lt;/span&gt;&lt;/p&gt;&lt;p&gt;&lt;span style=&quot;color: inherit;&quot;&gt;Donna&lt;/span&gt;&lt;/p&gt;&lt;p&gt;&lt;span style=&quot;color: inherit;&quot;&gt;Debt Free Americans CEO&lt;/span&gt;&lt;/p&gt;&lt;p&gt;&lt;span style=&quot;color: inherit;&quot;&gt;LinkedIn Link&lt;/span&gt;&lt;/p&gt;',
            ],
            [
                'code' => CommunicationCode::NEW_ACCOUNT->value,
                'name' => 'N-1 (EMAIL) NEW ACCOUNT FOR EXISTING PII',
                'subject' => 'Hi  [First Name], your [Original Name] account has been uploaded into your YouNegotiate Portal.',
                'type' => 'email',
                'content' => '&lt;p&gt;Hi [First Name],&lt;/p&gt;&lt;p&gt;&lt;br&gt;&lt;/p&gt;&lt;p&gt;The account below is ready for viewing and response in your YouNegotiate portal account with your Debt Free Americans Helping Hand link!&lt;/p&gt;&lt;p&gt;&lt;br&gt;&lt;/p&gt;&lt;p&gt;Account: [Original Name]&lt;/p&gt;&lt;p&gt;Account Number: [Original Account Number]&lt;/p&gt;&lt;p&gt;&lt;br&gt;&lt;/p&gt;&lt;p&gt;If you feel this is in error, please opt out to this email!&lt;/p&gt;&lt;p&gt;&lt;br&gt;&lt;/p&gt;&lt;p&gt;We are here 24/7 if you need any help!&lt;/p&gt;&lt;p&gt;&lt;br&gt;&lt;/p&gt;&lt;p&gt;&lt;strong&gt;YouNegotiate Team&lt;/strong&gt;&lt;/p&gt;&lt;p&gt;www.younegotiate.com&lt;/p&gt;&lt;p&gt;&lt;br&gt;&lt;/p&gt;&lt;p&gt;&lt;br&gt;&lt;/p&gt;',
            ],
            [
                'code' => CommunicationCode::UPDATE_PAY_TERMS_OFFER->value,
                'name' => 'T-1 (EMAIL) MEMBER UPDATED THE OFFER',
                'subject' => 'Notification:  [Original Name] updated their Term Offers in your YouNegotiate Portal Account.',
                'type' => 'email',
                'content' => '&lt;p&gt;Hi [First Name],&lt;/p&gt;&lt;p&gt;&lt;br&gt;&lt;/p&gt;&lt;p&gt;The term offer has been updated on your [Original Name] account (hopefully this is good news)!&lt;/p&gt;&lt;p&gt;Log into your portal account: [You Negotiate Link]&lt;/p&gt;&lt;p&gt;&lt;br&gt;&lt;/p&gt;&lt;p&gt;Our 24/7 live team is here if you need us: Email: help@younegotiate.com&lt;/p&gt;&lt;p&gt;&lt;br&gt;&lt;/p&gt;&lt;p&gt;Rock your day!&lt;/p&gt;&lt;p&gt;&lt;strong&gt;YouNegotiate team&lt;/strong&gt;&lt;/p&gt;',
            ],
            [
                'code' => CommunicationCode::CREDITOR_REMOVED_ACCOUNT->value,
                'name' => 'G-1 (EMAIL) REMOVED BY CREDITOR',
                'subject' => 'G-1 (EMAIL) REMOVED BY CREDITOR TEST - Your [Original Name] Account has been removed',
                'type' => 'email',
                'content' => '&lt;p&gt;&lt;br&gt;&lt;/p&gt;&lt;p&gt;Hi [First Name],&lt;/p&gt;&lt;p&gt;&lt;br&gt;&lt;/p&gt;&lt;p&gt;We are sorry to report that your [Original Name] account has been removed by the YouNegotiate member that posted it in your YouNegotiate portal account.&lt;/p&gt;&lt;p&gt;&lt;br&gt;&lt;/p&gt;&lt;p&gt;Log In to View:&lt;/p&gt;&lt;p&gt;[You Negotiate Link]&lt;/p&gt;&lt;p&gt;&lt;br&gt;&lt;/p&gt;&lt;p&gt;We are working hard to help you pay off your accounts so please do your best to jump into your account and respond as soon as you can!&lt;/p&gt;&lt;p&gt;&lt;br&gt;&lt;/p&gt;&lt;p&gt;&lt;strong&gt;YouNegotiate Team&lt;/strong&gt;&lt;/p&gt;&lt;p&gt;www.younegotiate.com&lt;/p&gt;',
            ],
            [
                'code' => CommunicationCode::ECO_MAIL_RECEIVED->value,
                'name' => 'ECO-1 (EMAIL) CONSUMER HAS NEW ECOLETTER',
                'subject' => 'ECO-1 (EMAIL) CONSUMER HAS NEW ECOLETTER  - You Have Secure ecoMail  [First Name]',
                'type' => 'email',
                'content' => '&lt;p&gt;Hi [First Name],&lt;/p&gt;&lt;p&gt;&lt;br&gt;&lt;/p&gt;&lt;p&gt;Just like Fedex, we&#039;re reaching out to let you know you have a new secure ecoLetter delivered into your YouNegotiate ecoMailbox regarding your&lt;/p&gt;&lt;p&gt;&lt;strong&gt;[Original Name] &lt;/strong&gt;account.&lt;/p&gt;&lt;p&gt;&lt;br&gt;&lt;/p&gt;&lt;p&gt;&lt;strong&gt;Log In:&lt;/strong&gt;&lt;/p&gt;&lt;p&gt;[You Negotiate Link]&lt;/p&gt;&lt;p&gt;&lt;br&gt;&lt;/p&gt;&lt;p&gt;&lt;strong style=&quot;color: rgb(0, 102, 204);&quot;&gt;Rock Your Day!&lt;/strong&gt;&lt;/p&gt;&lt;p&gt;&lt;strong&gt;YouNegotiate Team&lt;/strong&gt;&lt;/p&gt;&lt;p&gt;www.younegotiate.com&lt;/p&gt;',
            ],
            [
                'code' => CommunicationCode::CFPB_ECO_MAIL->value,
                'name' => 'ECO C-1 (EMAIL) CFPB ECOMAIL',
                'subject' => 'ECO C-1 (EMAIL) CFPB ECOMAIL TEST - Important: You have a Secure CFPB Letter',
                'type' => 'email',
                'content' => '&lt;p&gt;Hi [First Name],&lt;/p&gt;&lt;p&gt;&lt;br&gt;&lt;/p&gt;&lt;p&gt;This is to let you know an agency or law firm sent you a secure ecoLetter in regard to your [Original Name] account. Please log into your YouNegotiate portal account to view. Time is of the essence.&lt;/p&gt;&lt;p&gt;&lt;br&gt;&lt;/p&gt;&lt;p&gt;Deadline to Respond (if blank they did not provide): [Expiration date]&lt;/p&gt;&lt;p&gt;&lt;br&gt;&lt;/p&gt;&lt;p&gt;Remember you have your tax-deductible Debt Free Americans helping hand link and responding can stop legal actions and it only takes a few seconds to respond.&lt;/p&gt;&lt;p&gt;&lt;br&gt;&lt;/p&gt;&lt;p&gt;&lt;strong style=&quot;color: rgb(0, 102, 204);&quot;&gt;Log Into your YouNegotiate Portal Account:&lt;/strong&gt;&lt;/p&gt;&lt;p&gt;[You Negotiate Link]&lt;/p&gt;&lt;p&gt;&lt;br&gt;&lt;/p&gt;&lt;p&gt;We are here to support you 24/7!&lt;/p&gt;&lt;p&gt;&lt;br&gt;&lt;/p&gt;&lt;p&gt;&lt;strong&gt;YouNegotiate Team&lt;/strong&gt;&lt;/p&gt;&lt;p&gt;www.younegotiate.com&lt;/p&gt;&lt;p&gt;&lt;br&gt;&lt;/p&gt;&lt;p&gt;&lt;br&gt;&lt;/p&gt;',
            ],
            [
                'code' => CommunicationCode::COUNTER_OFFER_BUT_NO_RESPONSE->value,
                'name' => 'CO-2 (EMAIL) NEW COUNTER OFFER NOT VIEWED',
                'subject' => 'CO-2 (EMAIL) NEW COUNTER OFFER NOT VIEWED TEST - [Original Name] Counteroffer  Delivered',
                'type' => 'email',
                'content' => '&lt;p&gt;Hi [First Name],&lt;/p&gt;&lt;p&gt;&lt;br&gt;&lt;/p&gt;&lt;p&gt;Please jump in your YouNegotiate portal account to view the counteroffer on your [Original Name] account has soon as you can. It only takes a few seconds to eliminate the risk of the member deactivating your account for resolution. [You Negotiate Link]&lt;/p&gt;&lt;p&gt;&lt;br&gt;&lt;/p&gt;&lt;p&gt;Remember you have a tax deductible helping hand link to make settlement and active payment plan payments!&lt;/p&gt;&lt;p&gt;&lt;br&gt;&lt;/p&gt;&lt;p&gt;&lt;strong style=&quot;color: rgb(0, 102, 204);&quot;&gt;You got this!&lt;/strong&gt;&lt;/p&gt;&lt;p&gt;&lt;br&gt;&lt;/p&gt;&lt;p&gt;&lt;strong&gt;YouNegotiate Team&lt;/strong&gt;&lt;/p&gt;&lt;p&gt;wwwyounegotiate.com&lt;/p&gt;&lt;p&gt;&lt;br&gt;&lt;/p&gt;&lt;p&gt;&lt;br&gt;&lt;/p&gt;',
            ],
            [
                'code' => CommunicationCode::OFFER_APPROVED_BUT_NO_PAYMENT_SETUP->value,
                'name' => 'O-1 APPROVED OFFER/PENDING PAYMENT',
                'subject' => 'O-1 APPROVED OFFER/PENDING PAYMENT - Action Needed: Your [Original Name] Offer is Approved and Needs Payment Method before the expiration date',
                'type' => 'email',
                'content' => '&lt;p&gt;Great news [First Name]! Your offer to honor and resolve your [Original Name] account has been approved!&lt;/p&gt;&lt;p&gt;&lt;br&gt;&lt;/p&gt;&lt;p&gt;Please log into your portal account before the offer expires to GSD!&lt;/p&gt;&lt;p&gt;&lt;br&gt;&lt;/p&gt;&lt;p&gt;Log in: [You Negotiate Link]&lt;/p&gt;&lt;p&gt;&lt;br&gt;&lt;/p&gt;&lt;p&gt;&lt;strong&gt;GREAT JOB!!!!&lt;/strong&gt;&lt;/p&gt;&lt;p&gt;&lt;strong&gt;YouNegotiate Team&lt;/strong&gt;&lt;/p&gt;&lt;p&gt;www.younegotiate.com&lt;/p&gt;',
            ],
            [
                'code' => CommunicationCode::PAY_IN_PIF_AND_PAYMENT_SETUP_DONE->value,
                'name' => 'O-2 (EMAIL) NEGOTIATED SETTLEMENT PAYMENT PROCESSED!',
                'subject' => 'GREAT NEWS  [First Name]! Your  [Original Name] settlement offer was approved and your balance is ..... ZERO!',
                'type' => 'email',
                'content' => '&lt;p&gt;Looks like it&#039;s time to celebrate your victory [First Name]! We really respect you for honoring your accounts. INTEGRITY MEANS A LOT!&lt;/p&gt;&lt;p&gt;&lt;br&gt;&lt;/p&gt;&lt;p&gt;Log in to check out the ZERO Balance and again, don&#039;t forget to celebrate yourself! [You Negotiate Link]&lt;/p&gt;&lt;p&gt;&lt;br&gt;&lt;/p&gt;&lt;p&gt;All payment receipts will remain in your YouNegotiate portal account in case you ever need them!&lt;/p&gt;&lt;p&gt;&lt;br&gt;&lt;/p&gt;&lt;p&gt;YOU ROCK YOU ROCK YOU ROCK!!!!&lt;/p&gt;&lt;p&gt;&lt;br&gt;&lt;/p&gt;&lt;p&gt;Cheers,&lt;/p&gt;&lt;p&gt;&lt;strong&gt;The YouNegotiate Team&lt;/strong&gt;&lt;/p&gt;&lt;p&gt;www.younegotiate.com&lt;/p&gt;&lt;p&gt;&lt;br&gt;&lt;/p&gt;',
            ],
            [
                'code' => CommunicationCode::PAY_IN_INSTALLMENT_AND_PAYMENT_SETUP_DONE->value,
                'name' => 'O-3 (EMAIL) CREDITOR ACCEPTED PLAN OFFER/ SET UP',
                'subject' => 'O-3 (EMAIL) CREDITOR ACCEPTED PLAN OFFER/ SET UP TEST SUPER NEWS REGARDING YOUR [Original Name] OFFER',
                'type' => 'email',
                'content' => '&lt;p&gt;Great news [First Name]!&lt;/p&gt;&lt;p&gt;&lt;br&gt;&lt;/p&gt;&lt;p&gt;The payment plan offer you sent to honor your [Original Name] account was accepted and your payment plan is live in your &lt;strong&gt;YouNegotiate &lt;/strong&gt;account!&lt;/p&gt;&lt;p&gt;&lt;br&gt;&lt;/p&gt;&lt;p&gt;You can self-service your payment plan in your YouNegotiate portal to support your real-life circumstances: [You Negotiate Link]&lt;/p&gt;&lt;p&gt;&lt;br&gt;&lt;/p&gt;&lt;ol&gt;&lt;li data-list=&quot;ordered&quot;&gt;&lt;span class=&quot;ql-ui&quot; contenteditable=&quot;false&quot;&gt;&lt;/span&gt;DFA Tax Deductible Helping Hand Link so anyone can gift you help with payments and balances&lt;/li&gt;&lt;li data-list=&quot;ordered&quot;&gt;&lt;span class=&quot;ql-ui&quot; contenteditable=&quot;false&quot;&gt;&lt;/span&gt;&lt;strong&gt;Scheduled Payments&lt;/strong&gt;&lt;/li&gt;&lt;li data-list=&quot;ordered&quot; class=&quot;ql-indent-1&quot;&gt;&lt;span class=&quot;ql-ui&quot; contenteditable=&quot;false&quot;&gt;&lt;/span&gt;Change, Skip, Pay Early Payments&lt;/li&gt;&lt;li data-list=&quot;ordered&quot;&gt;&lt;span class=&quot;ql-ui&quot; contenteditable=&quot;false&quot;&gt;&lt;/span&gt;&lt;strong&gt;Payment Plan &lt;/strong&gt;&lt;/li&gt;&lt;li data-list=&quot;ordered&quot; class=&quot;ql-indent-1&quot;&gt;&lt;span class=&quot;ql-ui&quot; contenteditable=&quot;false&quot;&gt;&lt;/span&gt;Change Pay Method&lt;/li&gt;&lt;li data-list=&quot;ordered&quot; class=&quot;ql-indent-1&quot;&gt;&lt;span class=&quot;ql-ui&quot; contenteditable=&quot;false&quot;&gt;&lt;/span&gt;Change Recurring payment date(s)&lt;/li&gt;&lt;li data-list=&quot;ordered&quot; class=&quot;ql-indent-1&quot;&gt;&lt;span class=&quot;ql-ui&quot; contenteditable=&quot;false&quot;&gt;&lt;/span&gt;Download agreement and payment receipts&lt;/li&gt;&lt;li data-list=&quot;ordered&quot; class=&quot;ql-indent-1&quot;&gt;&lt;span class=&quot;ql-ui&quot; contenteditable=&quot;false&quot;&gt;&lt;/span&gt;Place a Temporary Hold on Plan&lt;/li&gt;&lt;li data-list=&quot;ordered&quot; class=&quot;ql-indent-1&quot;&gt;&lt;span class=&quot;ql-ui&quot; contenteditable=&quot;false&quot;&gt;&lt;/span&gt;Cancel/Close Plan&lt;/li&gt;&lt;/ol&gt;&lt;p&gt;&lt;br&gt;&lt;/p&gt;&lt;p&gt;Your plan is set up to do the needful in seconds to help you stay on track and knock it out!&lt;/p&gt;&lt;p&gt;&lt;br&gt;&lt;/p&gt;&lt;p&gt;We&#039;ll send you upcoming payment reminders so you can log in as needed!&lt;/p&gt;&lt;p&gt;&lt;br&gt;&lt;/p&gt;&lt;p&gt;Rock YOUR DAY!!! GREAT GREAT JOB honoring your commitments [First Name]! You&#039;re a WINNER!!!&lt;/p&gt;&lt;p&gt;&lt;br&gt;&lt;/p&gt;&lt;p&gt;&lt;strong style=&quot;color: rgb(0, 102, 204);&quot;&gt;YouNegotiateTeam!&lt;/strong&gt;&lt;/p&gt;&lt;p&gt;www.younegotiate.com&lt;/p&gt;&lt;p&gt;&lt;br&gt;&lt;/p&gt;&lt;p&gt;&lt;br&gt;&lt;/p&gt;&lt;p&gt;&lt;br&gt;&lt;/p&gt;',
            ],
            [
                'code' => CommunicationCode::PAYMENT_FAILED_WHEN_PIF->value,
                'name' => 'O (EMAIL) NEGOTATIATED / PAYMENT METHOD FAILED',
                'subject' => 'O (EMAIL) NEGOTATIATED / PAYMENT METHOD FAILED TEST',
                'type' => 'email',
                'content' => '&lt;p&gt;You negotiated a payment plan or settlement offer was approved, however the payment method on your offer failed. Please log in and finish up your payment before the offer expires!&lt;/p&gt;&lt;p&gt;&lt;br&gt;&lt;/p&gt;&lt;p&gt;You negotiated a great deal!&lt;/p&gt;&lt;p&gt;[You Negotiate Link]&lt;/p&gt;',
            ],
            [
                'code' => CommunicationCode::OFFER_DECLINED->value,
                'name' => 'O-4 (EMAIL) CREDITOR DECLINED OFFER',
                'subject' => 'O-4 (EMAIL) CREDITOR DECLINED OFFER TEST',
                'type' => 'email',
                'content' => '&lt;p&gt;YOUR CREDITOR DECLINED YOUR OFFER SO PLEASE RETART NEGOTIATIONS AND TRY AGAIN! &lt;/p&gt; [You Negotiate Link]',
            ],
            [
                'code' => CommunicationCode::THREE_DAY_EXPIRATION_DATE_REMINDER->value,
                'name' => 'PR-1 NEGOTIATED/ 3-DAYS TO EXPIRATION REMINDER TO ADD PAYMENT',
                'subject' => 'PR-1 NEGOTIATED/ 3-DAYS TO EXPIRATION REMINDER TO ADD PAYMENT TEST',
                'type' => 'email',
                'content' => '&lt;p&gt;PLEASE HURRY AND ADD YOUR PAYMENT TO YOUR NEGOTIATED OFFER ON YOUR [Original Name] ACCOUNT! IT&#039;S EXPIRING IN 3 DAYS!&lt;/p&gt;',
            ],
            [
                'code' => CommunicationCode::ONE_DAY_EXPIRATION_DATE_REMINDER->value,
                'name' => 'PR2 (EMAIL) OFFER EXPIRING IN 1 DAY ADD PAYMENT!',
                'subject' => 'PR2 (EMAIL) OFFER EXPIRING IN 1 DAY ADD PAYMENT! TEST',
                'type' => 'email',
                'content' => '&lt;p&gt;Your negotiated offer is expiring tomorrow. Please add your payment method and if settlement use your helping hand link. &lt;/p&gt;',
            ],
            [
                'code' => CommunicationCode::HELPING_HAND_FULL_PAY_SETTLED->value,
                'name' => 'HP1 (EMAIL) SOMEONE SETTLED THEIR BALANCE WITH THE HELPING HAND LINK!',
                'subject' => 'HP1 (EMAIL) SOMEONE SETTLED THEIR BALANCE WITH THE HELPING HAND LINK! TEST - WOO HOO SOMEONE PAID OFF YOUR [Original Name]!',
                'type' => 'email',
                'content' => '&lt;p&gt;SUPER EXCITING NEWS [First Name]! SOMEONE PAID OFF YOUR FULL BALANCE ON YOUR [Original Name] ACCOUNT FOR YOU!&lt;/p&gt;&lt;p&gt;&lt;br&gt;&lt;/p&gt;&lt;p&gt;LOG IN AND CHECK IT OUT!!&lt;/p&gt;&lt;p&gt;[You Negotiate Link]&lt;/p&gt;&lt;p&gt;&lt;br&gt;&lt;/p&gt;&lt;p&gt;THIS MEANS YOU ARE FREEEEE OF THIS DEBT!!!&lt;/p&gt;&lt;p&gt;&lt;br&gt;&lt;/p&gt;&lt;p&gt;GO CELEBRATE AND SEND OUR HUGS AND KISSES TO WHOMEVER MADE THE PAYMENT FOR YOU!!!&lt;/p&gt;',
            ],
            [
                'code' => CommunicationCode::HELPING_HAND_PAY_FULL_CURRENT_BALANCE->value,
                'name' => 'HP2 (EMAIL) HELPING HAND LINK SOMEONE PAID OFF THE PAYMENT PLAN BALANCE',
                'subject' => 'HP2 (EMAIL) HELPING HAND LINK SOMEONE PAID OFF THE PAYMENT PLAN BALANCE TEST',
                'type' => 'email',
                'content' => '&lt;p&gt;WOOOHOOOOOOOO GGUESS WHAT!!&lt;/p&gt;&lt;p&gt;&lt;br&gt;&lt;/p&gt;&lt;p&gt;SOMEONE USED YOUR HELPING HAND LINK AND PAID THE ENTIRE BALANCE ON YOUR PAYMENT PLAN!!!!!&lt;/p&gt;&lt;p&gt;&lt;br&gt;&lt;/p&gt;&lt;p&gt;WHAT&#039;S THIS MEAN?!?!???!?!?!?&lt;/p&gt;&lt;p&gt;&lt;br&gt;&lt;/p&gt;&lt;p&gt;FREEDOM!!!! &lt;/p&gt;&lt;p&gt;&lt;br&gt;&lt;/p&gt;&lt;p&gt;CONGRATS! SEND OUR LOVE AND HUGS TO THE PERSON THAT FREED YOU FROM THIS ACCOUNT!&lt;/p&gt;&lt;p&gt;&lt;br&gt;&lt;/p&gt;&lt;p&gt;CHECK IT OUT! &lt;/p&gt; [You Negotiate Link]',
            ],
            [
                'code' => CommunicationCode::HELPING_HAND_SUCCESSFUL_PAYMENT->value,
                'name' => 'HP-3 (EMAIL) HELPING HAND LINK SOMEONE MADE A PAYMENT PLAN PAYMENT',
                'subject' => 'HP-3 (EMAIL) HELPING HAND LINK SOMEONE MADE A PAYMENT PLAN PAYMENT - WOOOOHOOOOOO GUESS WHAT!',
                'type' => 'email',
                'content' => '&lt;p&gt;SOMEONE USED YOUR HELPING HAND LINK AND MADE A PAYMENT ON YOUR PAYMENT PLAN TO REDUCE YOUR BALANCE!&lt;/p&gt;&lt;p&gt;&lt;br&gt;&lt;/p&gt;&lt;p&gt;CHECK IT OUT IN YOUR PORTAL ACCOUNT AND SEND ALL OUR LOVE AND HUGS TO WHOMEVER IT WAS THAT HELPED YOU OUT!!!&lt;/p&gt;&lt;p&gt;&lt;br&gt;&lt;/p&gt;&lt;p&gt;LOG IN TO YOUR PORTAL ACCOUNT&lt;/p&gt;&lt;p&gt;&lt;br&gt;&lt;/p&gt;&lt;p&gt;[You Negotiate Link]&lt;/p&gt;',
            ],
        ];
    }
}
