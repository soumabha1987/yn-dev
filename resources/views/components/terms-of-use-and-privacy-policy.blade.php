@props(['buttonLabel' => __('Terms and Conditions')])

<x-dialog>
    <x-dialog.open>
        <button
            type="button"
            class="text-sm"
        >
            {{ $buttonLabel }}
        </button>
    </x-dialog.open>
    <x-dialog.panel size="3xl">
        <x-slot name="heading">
            <div class="text-left">
                <h3 class="text-sm sm:text-xl lg:text-2xl py-2 font-medium text-white text-nowrap">
                    {{ __('TERMS OF USE AND PRIVACY POLICY') }}
                </h3>
            </div>
        </x-slot>
        <div class="m-2">
            <div class="p-1 mt-2">
                <div>
                    <ol>
                        <li class="space-y-2">
                            <span class="text-black text-lg font-semibold">GENERAL TERMS.</span>
                            <p class="text-justify text-slate-800 pb-8">These Terms of Use and Privacy Policy (collectively, the “Agreement”) set forth the terms and conditions that apply to your access and use of the YouNegotiate.com website and mobile App (“Services”) as owned and operated by YouNegotiate®, a Nevada corporation, its subsidiaries, and/or affiliates (“YN”). As used in this Agreement, the term “Site” includes all Services websites, pages that are associated with or within each website, and all devices, applications, or services that YN operates or offers that link to this Agreement. By accepting electronically (for example, clicking “I Agree”), installing, accessing, or using the Services, you agree to be bound by the terms and conditions of this Agreement, as they may be amended from time to time in the future (see “Modifications” below). If you do not agree to be bound by this Agreement, then you may not use the Services.</p>
                            <ol>
                                <li class="my-2">
                                    <span class="text-black text-base font-semibold ml-5">1. &nbsp; Accepting the Terms</span>
                                    <p class="text-justify text-slate-800 pt-8">By using the information, tools, features, software and functionality including content, updates and new releases of the Services, you agree to be bound by this Agreement, whether you are a “Visitor” (which means that you simply browse the YouNegotiate.com website),  a “Consumer” (which means that you access your accounts through your free portal account) and/or a Creditor or Member (which means you have a membership account used to send all accounts and offers to consumers matching their name, last four of social and date of birth). The term “you” or “your” refers to your usage as either a Visitor, a Consumer and a Member.  The term “user” refers to anybody using the Services, including, without limitation, Visitors, Consumers, Payment Processors, or Creditors. The term “we,” “us,” or “our” refers to YN. If you wish to make use of the Services, you must read this Agreement and indicate your acceptance during the registration process to become a registered Consumer. The term “Creditor” shall mean any person or entity to which a debt or other obligation is owed including any agent of said Creditor assisting with collection of the debt. The term “Payment Processor” shall mean any credit card processor, bank, or other payment processor.</p>
                                    <p class="text-justify text-slate-800 pt-3">You may not use any of the Services and you may not accept this Agreement if you are not legally authorized to accept and be bound by these terms or are not at least 18 years of age and, in any event, of a legal age to form a binding contract with YN.</p>
                                    <p class="text-justify text-slate-800 py-8">Before you continue, you should print or save a local copy of this Agreement for your records.</p>
                                </li>
                                <li class="my-2">
                                    <span class="text-black text-base font-semibold ml-5">2. &nbsp; Description of the Services and Compensation</span><br/>
                                    <span class="text-black text-base font-semibold">YN supports 3 types of Users:</span>
                                    <p class="text-justify text-slate-800 pt-8">Consumers: YouNegotiate.com is a free service (to Consumers) that allows you to resolve your debt with a Creditor without speaking to the Creditor and/or its collectors. The actual resolution of the debt is between you and the Creditor, and we have no ability to bind you or the Creditor.</p>
                                    <p class="text-justify text-slate-800 py-3">Donators: YouNegotiate.com is a free service that enables anyone to make a direct payment to any consumer's delinquent account creditor.</p>
                                    <p class="text-justify text-slate-800 pb-8">Members: YouNegotiate.com offers memberships for any creditor, agency, law firm with consumer name, social and DOB associated with delinquent accounts. Members post their accounts and pay term offers and account offers and communications are delivered into a secure consumer portal account matching their name, social and DOB. Consumers action from their secure and private portal account on any delinquent account. All communication profiles are managed on the platform for 100% compliance.</p>
                                </li>
                                <li class="my-2">
                                    <span class="text-black text-base font-semibold ml-5">3. &nbsp; Your Account Information and Electronic Communications</span>
                                    <p class="text-justify text-slate-800 py-8">To allow you to use the Services, you must have the full name, last four digits of the social security and date of birth on your consumer accounts and will need to sign up as a member. We may verify your identity. You authorize us to make any inquiries we consider necessary to validate your identity. These inquiries may include asking you for further information, requiring you to provide your full name, mobile or other phone number(s), full address, bank account (including routing numbers) and/or credit card information, your date of birth, the last four digits of your social security number, information regarding your creditors, and/or requiring you to take steps to confirm ownership of your email address and your bank account and/or credit card information.  We have the right to verify information you provide against third-party databases or through other sources (including the Creditors). If you do not provide this information or YN cannot verify your identity, we can refuse to allow you to use the Services.</p>
                                    <p class="text-justify text-slate-800 pb-8">You agree and understand that you are responsible for maintaining the confidentiality of your password which, together with your Login ID (e.g., email address or other identifying name), allows you to access the Sites. That Login ID and password, any mobile or other phone number, or other information you provide (as described above) form your “Account Information.” By providing us with your e-mail address, you consent to receive all required notices and information.</p>
                                    <p class="text-justify text-slate-800 pb-8">Electronic communications may be posted on the Services site and/or delivered to your e-mail address that we have on file for you. It is your responsibility to promptly update us with your complete, accurate contact information, or change your information, including email address, as appropriate. Notices will be provided in HTML (or, if your system does not support HTML, in plain text) in the text of the e-mail or through a link to the appropriate page on our site, accessible through any standard, commercially available internet browser. Your consent to receive communications electronically is valid until you end your relationship with us.</p>
                                    <p class="text-justify text-slate-800 pb-8">You may print a copy of any electronic communications and retain it for your records. We reserve the right to terminate or change how we provide electronic communications and will provide you with appropriate notice in accordance with applicable law.</p>
                                    <p class="text-justify text-slate-800 pb-8">If you become aware of any unauthorized use of your Account Information, you agree to notify YN immediately at the following email address - <a href="mailto:help@YouNegotiate.com" target="_blank" class="text-primary hover:underline">help@YouNegotiate.com</a>.</p>
                                    <p class="text-justify text-slate-800 pb-8">If you believe that your Account Information or the device that you use to access the Services has been lost or stolen, or that someone is using your account without your permission, you must notify us immediately to minimize your losses.</p>
                                </li>
                                <li class="my-2">
                                    <span class="text-black text-base font-semibold ml-5">4. &nbsp; Your Use of the Services</span>
                                    <p class="text-justify text-slate-800 py-8">Your right to access and use the Sites and the Services is personal to you and is not transferable by you to any other person or entity. You are only entitled to access and use the Sites and Services for lawful purposes. Accurate records enable You Negotiate to provide the Services to you. You must provide true, accurate, current, and complete information, and you may not misrepresent your Account Information. For the Services to function effectively, you must also keep your Account Information up to date and accurate. If you do not do this, the accuracy and effectiveness of the Services will be affected. You represent that you are the legal owner of, and that you are authorized to provide us with, all Account Information and other information necessary to facilitate your use of the Services.</p>
                                    <p class="text-justify text-slate-800 pb-8">Your access and use of the Services may be interrupted from time to time for any of several reasons, including, without limitation, the malfunction of equipment, periodic updating, maintenance or repair of the Services or other actions that YN, in its sole discretion, may elect to take. In no event will YN be liable to any party for any loss, cost, or damage that results from any scheduled or unscheduled downtime.</p>
                                    <p class="text-justify text-slate-800 pb-8">Your sole and exclusive remedy for any failure or non-performance of the Services, including any associated software or other materials supplied in connection with such services, shall be for You Negotiate to use commercially reasonable efforts to effectuate an adjustment or repair of the applicable Service.</p>
                                </li>
                                <li class="my-2">
                                    <span class="text-black text-base font-semibold ml-5">5. &nbsp; Use With Your Mobile Device</span>
                                    <p class="text-justify text-slate-800 py-8">Use of these Services may be available through a compatible mobile device, internet and/or network access and may require software. You agree that you are solely responsible for these requirements, including any applicable changes, updates, and fees as well as the terms of your agreement with your mobile device and telecommunications provider. </p>
                                    <ol class="list-disc pb-8 space-y-2">
                                        <li class="text-slate-800 list-none">YN MAKES NO WARRANTIES OR REPRESENTATIONS OF ANY KIND, EXPRESS, STATUTORY OR IMPLIED AS TO: </li>
                                        <li class="ml-5 text-slate-800">&nbsp; THE AVAILABILITY OF TELECOMMUNICATION SERVICES FROM YOUR PROVIDER AND ACCESS TO THE SERVICES AT ANY TIME OR FROM ANY LOCATION; </li>
                                        <li class="ml-5 text-slate-800">&nbsp; ANY LOSS, DAMAGE, OR OTHER SECURITY INTRUSION OF THE TELECOMMUNICATION SERVICES; AND </li>
                                        <li class="ml-5 text-slate-800">&nbsp; ANY DISCLOSURE OF INFORMATION TO THIRD PARTIES OR FAILURE TO TRANSMIT ANY DATA, COMMUNICATIONS OR SETTINGS CONNECTED WITH THE SERVICES</li>
                                    </ol>
                                </li>
                                <li class="my-2">
                                    <span class="text-black text-base font-semibold ml-5">6. &nbsp; Online and Mobile Alerts </span>
                                    <p class="text-justify text-slate-800 py-8">YN from time to time to provide automatic alerts and voluntary account-related alerts. Automatic alerts may be sent to you following certain changes to your account or information, such as a change in your Account Information, that an offer to settle a debt has been sent, or confirmation that a payment has been made.</p>
                                    <p class="text-justify text-slate-800 pb-8">Voluntary account alerts may be turned on by default as part of the Services. These alerts allow you to choose alert messages for your accounts. YN may add new alerts from time to time or cease to provide certain alerts at any time upon its sole discretion. Each alert may have different options available, and you may be asked to select from among these options upon activation of your alerts service.</p>
                                    <p class="text-justify text-slate-800 pb-8">You understand and agree that any alerts provided to you through the Services may be delayed or prevented by a variety of factors. We may make commercially reasonable efforts to provide alerts in a timely manner with accurate information, but cannot guarantee the delivery, timeliness, or accuracy of the content of any alert. YN shall not be liable for any delays, failure to deliver, or misdirected delivery of any alert; for any errors in the content of an alert; or for any actions taken or not taken by you or any third party in reliance on an alert.</p>
                                    <p class="text-justify text-slate-800 pb-8">Electronic alerts will be sent to the email address or mobile number you have provided for the Services. If your email address or your mobile number changes, you are responsible for informing us of that change. Alerts may also be sent to a mobile device that accepts text messages. Changes to your email address, devices, and/or mobile number will apply to all alerts.</p>
                                </li>
                                <li class="my-2">
                                    <span class="text-black text-base font-semibold ml-5">7. &nbsp; Rights You Grant to Us </span>
                                    <p class="text-justify text-slate-800 py-8">By submitting Account Information, you are allowing ®® to use the Account Information for the purpose of providing the Services. We may use and store the content in accordance with this Agreement and our Privacy Policy. You represent that you are entitled to submit it to YN for use for this purpose, without any obligation by YN to pay any fees or be subject to any restrictions or limitations. By using the Services, you expressly authorize YN to access your Account Information maintained by identified third parties, on your behalf as your agent, and you expressly authorize such third parties to disclose your information to us.</p>
                                </li>
                                <li class="my-2">
                                    <span class="text-black text-base font-semibold ml-5">8. &nbsp; YouNegotiate's Intellectual Property Rights</span>
                                    <p class="text-justify text-slate-800 py-8">The contents of the Services, including its “look and feel” (e.g., text, graphics, images, logos, and button icons), photographs, editorial content, notices, software (including source and object code) and other material are protected under both United States and other applicable copyright, trademark, and other laws. The contents of the Services belong or are licensed to YN or its software or content suppliers. YN grants you the right to view and use the Services subject to the terms of this Agreement. You may download or print a copy of information for the Services for your personal, internal, and non-commercial use only. Any distribution,reprinting, or electronic reproduction of any content from the Services as a whole or for any other purpose is expressly prohibited without our prior written consent. You agree not to use, nor permit any third party to use, the Site or the Services or content in a manner that violates any applicable law, regulation, or the terms of this Agreement.</p>
                                </li>
                                <li class="my-2">
                                    <span class="text-black text-base font-semibold ml-5">9. &nbsp; Access and Interference</span>
                                    <ul class="py-8 list-disc space-y-2">
                                        <li class="text-justify text-slate-800 list-none pb-6">You agree that you will not:</li>
                                        <li class="ml-5 text-justify text-slate-800">Use any robot, spider, scraper, deep link, or other similar automated data gathering or extraction tools, program, algorithm, or methodology to access,acquire, copy, or monitor the Services or any portion of the Services, without YN's express written consent, which may be withheld in YN's sole discretion;
                                        </li>
                                        <li class="ml-5 text-justify text-slate-800">Use or attempt to use any engine, software, tool, agent, or other device or mechanism (including without limitation browsers, spiders, robots, avatars, or intelligent agents) to navigate or search the Services, other than the search engines and search agents available through the Services and other than generally available third party web browsers (e.g. Chrome, Microsoft Internet Explorer, Safari, etc.);</li>
                                        <li class="ml-5 text-justify text-slate-800">Post or transmit any file that contains viruses, worms, Trojan horses, or any other contaminating or destructive features, or that otherwise interfere with the proper working of the Services;
                                        </li>
                                        <li class="ml-5 text-justify text-slate-800">Attempt to copy, decipher, decompile, disassemble, or reverse-engineer any of the software comprising or in any way making up a part of the Services; or
                                        </li>
                                        <li class="ml-5 text-justify text-slate-800">Attempt to gain unauthorized access to any portion of the Services.</li>
                                    </ul>
                                </li>
                                <li class="my-2">
                                    <span class="text-black text-base font-semibold ml-5">10. &nbsp; Disclaimer of Representations and Warranties</span>
                                    <p class="text-justify text-slate-800 py-8">THE SITES, SERVICES, INFORMATION, DATA, FEATURES, AND ALL CONTENT AND ALL SERVICES AND PRODUCTS ASSOCIATED WITH THE SERVICES OR PROVIDED THROUGH THE SERVICES (WHETHER OR NOT SPONSORED) ARE PROVIDED TO YOU ON “AS-IS” AND “AS AVAILABLE” BASIS. YN, ITS AFFILIATES, AND ITS THIRD-PARTY PROVIDERS, LICENSORS, DISTRIBUTORS OR SUPPLIERS (COLLECTIVELY, “SUPPLIERS”) MAKE NO REPRESENTATIONS OR WARRANTIES OF ANY KIND, EXPRESS OR IMPLIED, AS TO THE CONTENT OR OPERATION OF THE SITE OR OF THE SERVICES. YOU EXPRESSLY AGREE THAT YOUR USE OF THE SERVICES IS AT YOUR SOLE RISK.</p>
                                    <p class="text-justify text-slate-800 pb-8">NEITHER YN OR ITS SUPPLIERS MAKE ANY REPRESENTATIONS, WARRANTIES OR GUARANTEES, EXPRESS OR IMPLIED, REGARDING THE ACCURACY, RELIABILITY OR COMPLETENESS OF THE CONTENT ON THE SITES OR OF THE SERVICES (WHETHER OR NOT SPONSORED), AND EXPRESSLY DISCLAIMS ANY WARRANTIES OF NON-INFRINGEMENT OR FITNESS FOR A PARTICULAR PURPOSE. NEITHER YN NOR ITS SUPPLIERS MAKE ANY REPRESENTATION, WARRANTY OR GUARANTEE THAT THE CONTENT THAT MAY BE AVAILABLE THROUGH THE SERVICES IS FREE OF INFECTION FROM ANY VIRUSES OR OTHER CODE OR COMPUTER PROGRAMMING ROUTINES THAT CONTAIN CONTAMINATING OR DESTRUCTIVE PROPERTIES OR THAT ARE INTENDED TO DAMAGE, SURREPTITIOUSLY INTERCEPT OR EXPROPRIATE ANY SYSTEM, DATA OR PERSONAL INFORMATION.</p>
                                    <p class="text-justify text-slate-800 pb-8">SOME JURISDICTIONS DO NOT ALLOW THE EXCLUSION OF CERTAIN WARRANTIES OR THE LIMITATION OR EXCLUSION OF LIABILITY FOR INCIDENTAL OR CONSEQUENTIAL DAMAGES. IN SUCH STATES LIABILITY IS LIMITED TO THE EXTENT PERMITTED BY LAW. ACCORDINGLY, SOME OF THE ABOVE LIMITATIONS OF SECTIONS 10 AND 11 OF THIS AGREEMENT MAY NOT APPLY TO YOU.</p>
                                    <p class="text-justify text-slate-800 pb-8">BY WAY OF EXAMPLE AND NOT LIMITATION, YN IS NOT RESPONSIBLE FOR (A) ANY COMMUNICATIONS BETWEEN YOU AND A CREDITOR (E.G. NEGOTIATIONS TO SETTLE A DEBT), (B) ANY PAYMENTS MADE BY YOU OR PAYMENT PROCESSORS, AND (C) WHETHER THE DEBT BEING NEGOTIATED IS ACCURATE OR ENFORCEABLE.</p>
                                </li>
                                <li class="my-2">
                                    <span class="text-black text-base font-semibold ml-5">11. &nbsp; Limitations on YouNegotiate's Liability</span>
                                    <p class="text-justify text-slate-800 py-8">YN SHALL IN NO EVENT BE RESPONSIBLE OR LIABLE TO YOU OR TO ANY THIRD PARTY, WHETHER IN CONTRACT, WARRANTY, TORT (INCLUDING NEGLIGENCE) OR OTHERWISE, FOR ANY DIRECT, INDIRECT, SPECIAL, INCIDENTAL, CONSEQUENTIAL, EXEMPLARY, LIQUIDATED OR PUNITIVE DAMAGES, INCLUDING BUT NOT LIMITED TO LOSS OF PROFIT, REVENUE OR BUSINESS, ARISING IN WHOLE OR IN PART FROM YOUR ACCESS TO THE SITES, YOUR USE OF THE SERVICES, THE SITES OR THIS AGREEMENT, EVEN IF YN HAS BEEN ADVISED OF THE POSSIBILITY OF SUCH DAMAGES. NOTWITHSTANDING ANYTHING TO THE CONTRARY IN THIS AGREEMENT, YN'S LIABILITY TO YOU FOR ANY CAUSE WHATEVER AND REGARDLESS OF THE FORM OF THE ACTION, WILL AT ALL TIMES BE LIMITED TO A MAXIMUM OF $100.00 (ONE HUNDRED UNITED STATES DOLLARS).</p>
                                </li>
                                <li class="my-2">
                                    <span class="text-black text-base font-semibold ml-5">12. &nbsp; Not a Legal, Tax, Financial Advisor, or Credit Repair Agency</span>
                                    <p class="text-justify text-slate-800 py-8">NEITHER YN NOR THE SERVICES ARE INTENDED TO PROVIDE LEGAL, TAX, OR FINANCIAL ADVICE. The Services are intended only to assist you in negotiating with your Creditors and trying to come to a mutually agreeable resolution of specific debts. Your personal financial situation is unique, and any information and advice obtained through the Service may not be appropriate for your situation. Accordingly, before making any final decisions regarding settlement of a debt, you should consider obtaining additional information and advice from your attorney, accountant, or other financial advisers who are fully aware of your individual circumstances.</p>
                                    <p class="text-justify text-slate-800 pb-8">You acknowledge that YN is not a credit repair company or similarly regulated organization under applicable laws and does not provide credit repair services. We do not provide any services to repair or improve your credit profile or credit score. Consult the services of a competent licensed professional when you need any type of assistance.</p>
                                </li>
                                <li class="my-2">
                                    <span class="text-black text-base font-semibold ml-5">13. &nbsp; Your Indemnification of YouNegotiate</span>
                                    <p class="text-justify text-slate-800 py-8">You shall defend, indemnify and hold harmless YN and its officers, directors, shareholders, employees, and agents from and against all claims, suits, proceedings, losses, liabilities, and expenses, whether in tort, contract, or otherwise, that arise out of or relate, including but not limited to attorneys' fees, in whole or in part arising out of or attributable to any breach of this Agreement, any activity by you in relation to the Sites, or your use of the Services.</p>
                                </li>
                                <li class="my-2">
                                    <span class="text-black text-base font-semibold ml-5">14. &nbsp; Ending your relationship with Us</span>
                                    <p class="text-justify text-slate-800 py-8">This Agreement will continue to apply until terminated by either you or YN. You will have the ability to close your account via your profile page.</p>
                                    <ol class="pb-8 list-disc space-y-2">
                                        <li class="text-slate-800 list-none">YN may at any time, terminate its legal agreement with you and access to the Services:</li>
                                        <li class="text-slate-800 ml-5">&nbsp; if you have breached any provision of this Agreement (or have acted in a manner which clearly shows that you do not intend to, or are unable to comply with the provisions of this Agreement); </li>
                                        <li class="text-slate-800 ml-5">&nbsp; if YN in its sole discretion believes it is required to do so by law (for example, where the provision of the Service to you is, or becomes, unlawful);</li>
                                        <li class="text-slate-800 ml-5">&nbsp; for any reason and at any time with or without notice to you; or</li>
                                        <li class="text-slate-800 ml-5">&nbsp; immediately upon notice to the e-mail address provided by you as part of your Account Information.</li>
                                    </ol>
                                    <p class="text-justify text-slate-800 pb-8">You acknowledge and agree that YN may immediately deactivate or delete your account and all related information and files in your account and/or prohibit any further access to all files and the Services by you. Further, you agree that YN shall not be liable to you or any third party for any termination of your access to the Services.</p>
                                </li>
                                <li class="my-2">
                                    <span class="text-black text-base font-semibold ml-5">15. &nbsp; Release</span>
                                    <p class="text-justify text-slate-800 py-8">IF YOU HAVE A DISPUTE AGAINST ANOTHER USER (INCLUDING CREDITORS), YOU RELEASE US (AND OUR AFFILIATES AND SUBSIDIARIES, AND OUR AND THEIR RESPECTIVE OFFICERS, DIRECTORS, EMPLOYEES AND AGENTS) FROM CLAIMS, DEMANDS AND DAMAGES (ACTUAL AND CONSEQUENTIAL) OF EVERY KIND AND NATURE, KNOWN AND UNKNOWN, ARISING OUT OF OR IN ANY WAY CONNECTED WITH ANY SUCH DISPUTE. IN ENTERING INTO THIS RELEASE YOU EXPRESSLY WAIVE ANY PROTECTIONS (WHETHER STATUTORY OR OTHERWISE) THAT WOULD OTHERWISE LIMIT THE COVERAGE OF THIS RELEASE TO INCLUDE ONLY THOSE CLAIMS WHICH YOU MAY KNOW OR SUSPECT TO EXIST IN YOUR FAVOR AT THE TIME OF AGREEING TO THIS RELEASE.</p>
                                </li>
                                <li class="my-2">
                                    <span class="text-black text-base font-semibold ml-5">16. &nbsp; Modifications</span>
                                    <p class="text-justify text-slate-800 py-8">YN reserves the right at any time and from time to time to modify or discontinue, temporarily or permanently, the Sites or Services with or without notice. YN reserves the right to change the Services, in our sole discretion and from time to time. If you do not agree to the changes after receiving notice of the change to the Services, you must stop using the Services. Your use of the Services, after you are notified of any change(s) will constitute your agreement to such change(s). You agree that YN shall not be liable to you or to any third party for any modification, suspension, or discontinuance of the Services.</p>
                                    <p class="text-justify text-slate-800 pb-8">YN may modify this Agreement from time to time. All changes to this Agreement may be provided to you by electronic means (i.e., via email or by posting the information on the Sites). In addition, the Agreement will always indicate the date it was last revised. You are deemed to accept and agree to be bound by any changes to the Agreement when you use the Services after those changes are posted.</p>
                                </li>
                                <li class="my-2">
                                    <span class="text-black text-base font-semibold ml-5">17. &nbsp; Governing Law and Forum for Disputes</span>
                                    <p class="text-justify text-base font-semibold my-2 text-black py-8">PLEASE READ THIS SECTION CAREFULLY. IT AFFECTS YOUR RIGHTS AND WILL HAVE A SUBSTANTIAL IMPACT ON HOW CLAIMS YOU AND YN HAVE AGAINST EACH OTHER ARE RESOLVED.</p>
                                    <p class="text-justify text-slate-800 font-semibold pb-8">You and YN agree that any claim or dispute at law or equity that has arisen or may arise, between you and YN (including any claim or dispute between you and a third-party agent of YN) that relates in any way to or arises out of this or previous versions of this Agreement, your use of or access to the Services, the actions of YN or its agents, or any products or services sold or purchased through the Services, will be resolved in accordance with the provisions set forth in this Section.</p>
                                </li>
                                <li class="my-2">
                                    <span class="text-black text-base font-semibold ml-5">18. &nbsp; Applicable Law</span>
                                    <p class="text-justify text-slate-800 py-8">You agree that except to the extent inconsistent with or preempted by federal law, the laws of the State of Ohio, without regard to principles of conflict of laws, will govern this Agreement and any claim or dispute that has arisen or may arise between you and YN, except as otherwise stated in this Agreement.</p>
                                </li>
                                <li class="my-2">
                                    <span class="text-black text-base font-semibold ml-5">19. &nbsp; Agreement to Arbitrate</span>
                                    <p class="text-justify text-slate-800 font-semibold py-8">You and YN each agree that any and all disputes or claims that have arisen, or may arise, between you and YN (including any disputes or claims between you and a third-party agent of YN) that relate in any way to or arise out of this or previous versions of the Agreement,your use of or access to the Services, the actions of YN or its agents, or any products or services sold, offered, or purchased through the Services shall be resolved exclusively through final and binding arbitration, rather than in court (“Agreement to Arbitrate”). Alternatively, you may assert your claims in small claims court, if your claims qualify and so long as the matter remains in such court and advances only on an individual (non-class, non-representative) basis. The Federal Arbitration Act governs the interpretation and enforcement of this Agreement to Arbitrate.</p>
                                </li>
                                <li class="my-2">
                                    <span class="text-black text-base font-semibold ml-5">20. &nbsp; Prohibition of Class and Representative Actions and Non-Individualized Relief</span>
                                    <p class="text-justify text-slate-800 py-8"><span class="font-semibold">YOU AND YN AGREE THAT EACH OF US MAY BRING CLAIMS AGAINST THE OTHER ONLY ON AN INDIVIDUAL BASIS AND NOT AS A PLAINTIFF OR CLASS MEMBER IN ANY PURPORTED CLASS, OR REPRESENTATIVE OR PRIVATE ATTORNEY GENERAL ACTION OR PROCEEDING. UNLESS BOTH YOU AND YN AGREE OTHERWISE, THE ARBITRATOR MAY NOT CONSOLIDATE OR JOIN MORE THAN ONE PERSON'S OR PARTY'S CLAIMS, AND MAY NOT OTHERWISE PRESIDE OVER ANY FORM OF A CONSOLIDATED, REPRESENTATIVE, CLASS, OR PRIVATE ATTORNEY GENERAL ACTION OR PROCEEDING. ALSO, THE ARBITRATOR MAY AWARD RELIEF (INCLUDING MONETARY, INJUNCTIVE, AND DECLARATORY RELIEF) ONLY IN FAVOR OF THE INDIVIDUAL PARTY SEEKING RELIEF AND ONLY TO THE EXTENT NECESSARY TO PROVIDE RELIEF NECESSITATED BY THAT PARTY'S INDIVIDUAL CLAIM(S). ANY RELIEF AWARDED CANNOT AFFECT OTHER USERS.</span> If a court decides that applicable law precludes enforcement of any of this paragraph's limitations as to a particular claim for relief, then that claim (and only that claim) must be severed from the arbitration and may be brought in court, subject to your and YN's right to appeal the court's decision. All other claims will be arbitrated.</p>
                                </li>
                                <li class="my-2">
                                    <span class="text-black text-base font-semibold ml-5">21. &nbsp; Arbitration Procedures</span>
                                    <p class="text-justify text-slate-800 py-8">Arbitration is more informal than a lawsuit in court. Arbitration uses a neutral arbitrator instead of a judge or jury, and court review of an arbitration award is limited. However, an arbitrator can award the same damages and relief on an individual basis that a court can award to an individual. An arbitrator should apply the terms of the Agreement as a court would. All issues are for the arbitrator to decide, except those issues relating to arbitrability, the scope or enforceability of this Agreement to Arbitrate, or the interpretation of Section 17(B)(1) (“Prohibition of Class and Representative Actions and Non-Individualized Relief”), shall be for a court of competent jurisdiction to decide.</p>
                                    <p class="text-justify text-slate-800 pb-8">The arbitration will be conducted by the American Arbitration Association (“AAA”) under its rules and procedures, including the AAA's Consumer Arbitration Rules (as applicable), as modified by this Agreement to Arbitrate. The AAA's rules are available at www.adr.org. The use of the word “arbitrator” in this provision shall not be construed to prohibit more than one arbitrator from presiding over an arbitration; rather, the AAA's rules will govern the number of arbitrators that may preside over an arbitration conducted under this Agreement to Arbitrate.</p>
                                    <p class="text-justify text-slate-800 pb-8">A party who intends to seek arbitration must first send to the other, by certified mail, a completed form Notice of Dispute (“Notice”):
                                    </p>
                                    <span class="my-2 text-slate-800">
                                        <p class="pb-1.5">YouNegotiate®</p>
                                        <p class="pb-1.5">11239 Ventura Blvd Suite 103-162</p>
                                        <p class="pb-1.5">Studio City, CA 91604</p>
                                        <p class="pb-1.5">Attention: Notice to Dispute</p>
                                    </span>
                                    <p class="text-justify text-slate-800 py-8">YN will send all Notices to the physical address we have on file associated with your YN account; it is your responsibility to keep your physical address up to date. All information given in the Notice must be provided, including a description of the nature and basis of the claims the party is asserting, and the relief sought.</p>
                                    <p class="text-justify text-slate-800 pb-8">If you and YN are unable to resolve the claims described in the Notice within 30 days after the Notice is sent, you or YN may initiate arbitration proceedings. A form for initiating arbitration proceedings is available on the AAA's site at www.adr.org. In addition to filing this form with the AAA in accordance with its rules and procedures, the party initiating the arbitration must mail a copy of the completed form to the opposing party. You may send a copy to YN at the foregoing address. In the event YN initiates an arbitration against you, it will send a copy of the completed form to the physical address we have on file associated with your YN account. Any settlement offers made by you or YN shall not be disclosed to the arbitrator.</p>
                                    <p class="text-justify text-slate-800 pb-8">The arbitration hearing shall be held in the county in which you reside or at another mutually agreed location. If the value of the relief sought is $10,000 or less, you or YN may elect to have the arbitration conducted by telephone or based solely on written submissions, which election shall be binding on you and YN subject to the arbitrator's discretion to require an in-person hearing, if the circumstances warrant. In cases where an in-person hearing is held, you and/or YN may attend by telephone, unless the arbitrator requires otherwise.</p>
                                    <p class="text-justify text-slate-800 pb-8">The arbitrator will decide the substance of all claims in accordance with applicable law, including recognized principles of equity, and will honor all claims of privilege recognized by law. The arbitrator shall not be bound by rulings in prior arbitrations involving different users but is bound by rulings in prior arbitrations involving the same YN user to the extent required by applicable law. The arbitrator's award shall be final and binding and judgment on the award rendered by the arbitrator may be entered in any court having jurisdiction thereof.</p>
                                </li>
                                <li class="my-2">
                                    <span class="text-black text-base font-semibold ml-5">3. &nbsp; Costs of Arbitration</span>
                                    <p class="text-justify text-slate-800 py-8">Payment of all filing, administration and arbitrator fees will be governed by the AAA's rules, unless otherwise stated in this Agreement to Arbitrate. If the value of the relief sought is $10,000 or less, at your request, YN will pay all filing, administration, and arbitrator fees associated with the arbitration. Any request for payment of fees by YN should be submitted by mail to the AAA along with your Demand for Arbitration and YN will decide to pay all necessary fees directly to the AAA. If (a) you willfully fail to comply with the Notice of Dispute requirement discussed above, or (b) in the event the arbitrator determines the claim(s) you assert in the arbitration to be frivolous, you agree to reimburse YN for all fees associated with the arbitration paid by YN on your behalf that you otherwise would be obligated to pay under the AAA's rules.</p>
                                </li>
                                <li class="my-2">
                                    <span class="text-black text-base font-semibold ml-5">4. &nbsp; Severability</span>
                                    <p class="text-justify text-slate-800 py-8">Except for any of the provisions in Section 17(B)(1) of this Agreement to Arbitrate (“Prohibition of Class and Representative Actions and Non-Individualized Relief”), if an arbitrator or court decides that any part of this Agreement to Arbitrate is invalid or unenforceable, the other parts of this Agreement to Arbitrate shall still apply.</p>
                                </li>
                                <li class="my-2">
                                    <span class="text-black text-base font-semibold ml-5">5. &nbsp; Opt-Out Procedure</span>
                                    <p class="text-justify font-semibold text-slate-800 py-8">IF YOU ARE A NEW USER OF OUR SERVICES, YOU CAN CHOOSE TO REJECT THIS AGREEMENT TO ARBITRATE (“OPT-OUT”) BY MAILING US A WRITTEN OPT-OUT NOTICE (“OPT-OUT NOTICE”). THE OPT-OUT NOTICE MUST BE POSTMARKED NO LATER THAN 30 DAYS AFTER THE DATE YOU ACCEPT THIS AGREEMENT FOR THE FIRST TIME. YOU MUST MAIL THE OPT-OUT NOTICE TO:</p>
                                    <span class="my-2 text-slate-800">
                                        <p class="pb-1.5">YouNegotiate®</p>
                                        <p class="pb-1.5">11239 Ventura Blvd Suite 103-163</p>
                                        <p class="pb-1.5">Studio City, CA 91604</p>
                                        <p class="pb-1.5">Attention: Legal Team</p>
                                    </span>
                                    <p class="text-justify text-slate-800 py-8">You must complete and mail that to us to opt out of the Agreement to Arbitrate. You must provide your name, address (including street address, city, state, and zip code), Login ID and email address to which the opt-out applies. You must sign the Opt-Out Notice for it to be effective. This procedure is the only way you can opt out of the Agreement to Arbitrate. If you opt out of the Agreement to Arbitrate, all other parts of this Agreement and its Legal Disputes Section will continue to apply to you. Opting out of this Agreement to Arbitrate has no effect on any previous, other, or future arbitration agreements that you may have with us.</p>
                                </li>
                                <li class="my-2">
                                    <span class="text-black text-base font-semibold ml-5">6. &nbsp; Future Amendments to the Agreement to Arbitrate</span>
                                <p class="text-justify text-slate-800 py-8">Notwithstanding any provision in the Agreement to the contrary, you and YN agree that if we make any amendment to this Agreement to Arbitrate (other than an amendment to any notice address or site link provided herein) in the future, that amendment shall not apply to any claim that was filed in a legal proceeding against YN prior to the effective date of the amendment. The amendment shall apply to all other disputes or claims governed by the Agreement to Arbitrate that have arisen or may arise between you and YN. If you do not agree to the amended terms, you must close your account within the 30-day period and you will not be bound by the amended terms.</p>
                                </li>
                                <li class="my-2">
                                    <span class="text-black text-base font-semibold ml-5">7. &nbsp; Judicial Forum for Legal Disputes</span>
                                    <p class="text-justify text-slate-800 py-8">Unless you and we agree otherwise, in the event that the Agreement to Arbitrate above is found not to apply to you or to a particular claim or dispute, either as a result of your decision to opt out of the Agreement to Arbitrate or as a result of a decision by the arbitrator or a court order, you agree that any claim or dispute that has arisen or may arise between you and YN must be resolved exclusively by a state or federal court located in Montgomery County, Ohio. You and YN agree to submit to the personal jurisdiction of the courts located within Montgomery County, Ohio for the purpose of litigating all such claims or disputes.</p>
                                </li>
                                <li class="my-2">
                                    <span class="text-black text-base font-semibold ml-5">8. &nbsp;  Third Party Terms and Conditions</span>
                                    <p class="text-justify text-slate-800 py-8">Your use of the Site may be subject to other terms and conditions of third parties, including, without limitation, those terms, and conditions of any applicable App Store. Compliance with those terms and conditions are solely your responsibility.</p>
                                </li>
                                <li class="my-2">
                                    <span class="text-black text-base font-semibold ml-5">9. &nbsp;   Allegations of Copyright and Trademark Infringements; Notification</span>
                                    <p class="text-justify text-slate-800 py-8">YN respects the intellectual property rights of others and YN asks that users of the Site and Services do the same. We respond to notices of alleged copyright infringement under the United States Digital Millennium Copyright Act. If you believe that your intellectual property rights have been infringed, please notify our Designated Agent (as set forth below) and we will investigate.</p>
                                    <span class="my-2 text-slate-800">
                                        <p class="pb-1.5">YouNegotiate®</p>
                                        <p class="pb-1.5">11239 Ventura Blvd Suite 103-163</p>
                                        <p class="pb-1.5">Studio City, CA 91604</p>
                                        <p class="pb-1.5">Attention: Legal Team</p>
                                    </span>
                                </li>
                                <li class="my-2 pt-4">
                                    <span class="text-black text-base font-semibold ml-5">20. &nbsp;  Transfer upon Sale</span>
                                    <p class="text-justify text-slate-800 py-8">Notwithstanding anything in this Agreement to the contrary, YN may transfer your information (including Account Information) to any purchaser of YN (whether by stock sale, asset sale, merger, consolidation, or any other method of transfer).</p>
                                </li>
                            </ol>
                        </li>
                        <li class="space-y-2 mt-3">
                            <h2 class="text-black text-lg font-semibold">PRIVACY POLICY</h2>
                            <ol>
                                <li class="my-2">
                                    <p class="text-black text-base font-semibold py-4 ml-5">1. &nbsp;  Types of Personal Information (“PI” or “Personal Information”) Collected and Uses.</p>
                                    <ol>
                                        <li class="my-2">
                                            <h3 class="text-black text-lg font-medium underline pb-4">CONTACT INFORMATION</h3>

                                            <ol>
                                                <li class="my-2">
                                                    <span class="text-black font-semibold">Category and Sources of Personal Information</span>
                                                    <ul class="list-disc space-y-2 py-8">
                                                        <li class="text-slate-800 ml-6">You (the Consumer)</li>
                                                        <li class="text-slate-800 ml-6">Creditors and loan originators (when they upload your account information)</li>
                                                        <li class="text-slate-800 ml-6">Third parties, such as companies that help us maintain the accuracy of our information</li>
                                                    </ul>
                                                </li>
                                                <li class="my-2">
                                                    <span class="text-black font-semibold">Representative Information Elements</span>
                                                    <p class="text-slate-800 pt-8">Types of information in this category include:</p>
                                                    <ul class="list-disc space-y-2 py-8">
                                                        <li class="text-slate-800 ml-6">Last four digits of your social security number</li>
                                                        <li class="text-slate-800 ml-6">Full date of birth</li>
                                                        <li class="text-slate-800 ml-6">Full name</li>
                                                        <li class="text-slate-800 ml-6">Mailing address</li>
                                                        <li class="text-slate-800 ml-6">Email address</li>
                                                        <li class="text-slate-800 ml-6">Mobile or other phone numbers</li>
                                                        <li class="text-slate-800 ml-6">Information regarding your Creditors/accounts and amounts owed (if sending invitation or offer to a creditor not yet on the YouNegotiate network)</li>
                                                        <li class="text-slate-800 ml-6">Credit card information</li>
                                                        <li class="text-slate-800 ml-6">Bank account information</li>
                                                    </ul>
                                                </li>
                                                <li class="my-2">
                                                    <span class="text-black font-semibold">Purpose for Collecting and Sharing the PI</span>
                                                    <p class="text-slate-800 pt-8">To identify you and communicate with you, including:</p>
                                                    <ul class="list-disc space-y-2 py-8">
                                                        <li class="text-slate-800 ml-6">Match creditor accounts to Consumer profile</li>
                                                        <li class="text-slate-800 ml-6">Send transactional messages (payment confirmation, offer conformations, etc.)</li>
                                                        <li class="text-slate-800 ml-6">Creditor to contact you regarding an offer or account (note you control this at the offer level)</li>
                                                        <li class="text-slate-800 ml-6">Send communications, surveys and invitations</li>
                                                        <li class="text-slate-800 ml-6">Personalize our communications and provide customer service</li>
                                                        <li class="text-slate-800 ml-6">Everyday business purposes</li>
                                                    </ul>
                                                </li>
                                                <li class="my-2">
                                                    <span class="text-black font-semibold">Categories of Third Parties to whom this type of Personal Information is Disclosed for a Business Purpose</span>
                                                    <p class="text-slate-800 pt-8">We may disclose this type of information as described in this Privacy Policy, including:</p>
                                                    <ul class="list-disc space-y-2 py-8">
                                                        <li class="text-slate-800 ml-6">Our affiliates (“Affiliates”)</li>
                                                        <li class="text-slate-800 ml-6">Creditors</li>
                                                        <li class="text-slate-800 ml-6">Our service providers (“Service Providers”)</li>
                                                        <li class="text-slate-800 ml-6">Third parties who deliver our communications</li>
                                                    </ul>
                                                </li>
                                                <li class="my-2">
                                                    <span class="text-black font-semibold">Categories of Third Parties to whom this type of Personal Information is Sold</span>
                                                    <p class="text-slate-800 py-8">We do not sell this personal information.</p>
                                                </li>
                                            </ol>
                                        </li>
                                        <li class="my-2">
                                            <h3 class="text-black text-lg font-medium underline pb-4">GOVERNMENT-ISSUED IDENTIFICATION INFORMATION NUMBERS</h3>
                                            <ol>
                                                <li class="my-2">
                                                    <span class="text-black font-semibold">Category and Sources of Personal Information</span>
                                                    <p class="text-slate-800 pt-8">We collect this type of information from various sources, including from:  </p>
                                                    <ul class="list-disc space-y-2 py-8">
                                                        <li class="text-slate-800 ml-6">You </li>
                                                        <li class="text-slate-800 ml-6">Payment Processors, Creditors, etc.</li>
                                                        <li class="text-slate-800 ml-6"> Third parties (e.g. entities which verify the information you provided).</li>
                                                    </ul>
                                                </li>
                                                <li class="my-2">
                                                    <span class="text-black font-semibold">Representative Information Elements</span>
                                                    <p class="text-slate-800 pt-8">Types of information in this category include: </p>
                                                    <ul class="list-disc space-y-2 py-8">
                                                        <li class="text-slate-800 ml-6">Last four digits of your social security number</li>
                                                        <li class="text-slate-800 ml-6">Other government-issued identifiers as may be needed for compliance with law or to maintain the security and safety of your account and our services.</li>
                                                    </ul>
                                                </li>
                                                <li class="my-2">
                                                    <span class="text-black font-semibold">Purpose for Collecting and Sharing the PI</span>
                                                    <p class="text-slate-800 pt-8">We use personal information as described in this Privacy Policy, including:</p>
                                                    <ul class="list-disc space-y-2 py-8">
                                                        <li class="text-slate-800 ml-6">To identify you</li>
                                                        <li class="text-slate-800 ml-6">To maintain the integrity of our records</li>
                                                        <li class="text-slate-800 ml-6">For verification purposes</li>
                                                        <li class="text-slate-800 ml-6">For security and risk management, fraud prevention and similar purposes</li>
                                                        <li class="text-slate-800 ml-6">For our everyday business purposes</li>
                                                    </ul>
                                                </li>
                                                <li class="my-2">
                                                    <span class="text-black font-semibold">Categories of Third Parties to whom this type of personal Information is Disclosed for a Business Purpose</span>
                                                    <p class="text-slate-800 pt-8">We may disclose this type of information as described in this Privacy Policy, including to:</p>
                                                    <ul class="list-disc space-y-2 py-8">
                                                        <li class="text-slate-800 ml-6">Affiliates</li>
                                                        <li class="text-slate-800 ml-6">Service Providers</li>
                                                        <li class="text-slate-800 ml-6">Payment Processors, Creditors, etc.</li>
                                                    </ul>
                                                </li>
                                                <li class="my-2">
                                                    <span class="text-black font-semibold">Categories of Third Parties to whom this type of Personal Information is Sold</span>
                                                    <p class="text-slate-800 py-8">We do not sell this personal information.</p>
                                                </li>
                                            </ol>
                                        </li>
                                        <li class="my-2">
                                            <h3 class="text-black text-lg font-medium underline pb-4">UNIQUE IDENTIFIERS</h3>
                                            <ol>
                                                <li class="my-2">
                                                    <span class="text-black font-semibold">Category and Sources of Personal Information</span>
                                                    <p class="text-slate-800 pt-8">We assign unique identifiers to you when you register. </p>
                                                    <p class="text-slate-800 pt-8">We collect this type of information from various sources, including from:</p>
                                                    <ul class="list-disc space-y-2 py-8">
                                                        <li class="text-slate-800 ml-6">You </li>
                                                        <li class="text-slate-800 ml-6"> Third parties </li>
                                                    </ul>
                                                </li>
                                                <li class="my-2">
                                                    <span class="text-black font-semibold">Representative Information Elements</span>
                                                    <p class="text-slate-800 pt-8">Types of information in this category include:</p>
                                                    <ul class="list-disc space-y-2 py-8">
                                                        <li class="text-slate-800 ml-6">Customer or account number</li>
                                                        <li class="text-slate-800 ml-6">System identifiers (e.g., Login ID and password)</li>
                                                        <li class="text-slate-800 ml-6">Device identifier</li>
                                                    </ul>
                                                </li>
                                                <li class="my-2">
                                                    <span class="text-black font-semibold">Purpose for Collecting and Sharing the PI</span>
                                                    <p class="text-slate-800 pt-8">We use personal information as described in this Privacy Policy, including:</p>
                                                    <ul class="list-disc space-y-2 py-8">
                                                        <li class="text-slate-800 ml-6">To identify you or your device, including to associate you with different devices that you may use</li>
                                                        <li class="text-slate-800 ml-6">For our everyday business purposes</li>
                                                    </ul>
                                                </li>
                                                <li class="my-2">
                                                    <span class="text-black font-semibold">Categories of Third Parties to whom this type of Personal Information is Disclosed for a Business Purpose</span>
                                                    <p class="text-slate-800 pt-8">We may disclose this type of information as described in this Privacy Policy, including to:</p>
                                                    <ul class="list-disc space-y-2 py-8">
                                                        <li class="text-slate-800 ml-6">Affiliates</li>
                                                        <li class="text-slate-800 ml-6">Service Providers</li>
                                                        <li class="text-slate-800 ml-6">Payment Processors, Creditors, etc.</li>
                                                    </ul>
                                                </li>
                                                <li class="my-2">
                                                    <span class="text-black font-semibold">Categories of Third Parties to whom this type of Personal Information is Sold</span>
                                                    <p class="text-slate-800 py-8">We do not sell this personal information.</p>
                                                </li>
                                            </ol>
                                        </li>
                                        <li class="my-2">
                                            <h3 class="text-black text-lg font-medium underline pb-4">TRANSACTION AND INTERACTION INFORMATION</h3>
                                            <ol>
                                                <li class="my-2">
                                                    <span class="text-black font-semibold">Category and Sources of Personal Information</span>
                                                    <p class="text-slate-800 pt-8">We collect this type of information from various sources, including from:</p>
                                                    <ul class="list-disc space-y-2 py-8">
                                                        <li class="text-slate-800 ml-6">You </li>
                                                        <li class="text-slate-800 ml-6">Payment Processors, Creditors, etc.</li>
                                                        <li class="text-slate-800 ml-6">Third parties</li>
                                                    </ul>
                                                </li>
                                                <li class="my-2">
                                                    <span class="text-black font-semibold">Representative Information Elements</span>
                                                    <p class="text-slate-800 pt-8">Types of information in this category include:</p>
                                                    <ul class="list-disc space-y-2 py-8">
                                                        <li class="text-slate-800 ml-6">Consumer Account Information</li>
                                                        <li class="text-slate-800 ml-6">Interactions with Payment Processors, Creditors, etc.</li>
                                                        <li class="text-slate-800 ml-6">Bank account, credit card, or other payment information</li>
                                                    </ul>
                                                </li>
                                                <li class="my-2">
                                                    <span class="text-black font-semibold">Purpose for Collecting and Sharing the PI</span>
                                                    <p class="text-slate-800 pt-8">We use personal information as described in this Privacy Policy, including:</p>
                                                    <ul class="list-disc space-y-2 py-8">
                                                        <li class="text-slate-800 ml-6">To fulfill our business relationship with you, including customer service</li>
                                                        <li class="text-slate-800 ml-6">For recordkeeping and compliance, including dispute resolution</li>
                                                        <li class="text-slate-800 ml-6">For internal business purposes, such as finance, quality control, training, reporting and analytics</li>
                                                        <li class="text-slate-800 ml-6">For risk management, fraud prevention and similar purposes</li>
                                                        <li class="text-slate-800 ml-6">For our everyday business purposes</li>
                                                    </ul>
                                                </li>
                                                <li class="my-2">
                                                    <span class="text-black font-semibold">Categories of Third Parties to whom this type of Personal Information is Disclosed for a Business Purpose</span>
                                                    <p class="text-slate-800 pt-8">We may disclose this type of information as described in this Privacy Policy, including to:</p>
                                                    <ul class="list-disc space-y-2 py-8">
                                                        <li class="text-slate-800 ml-6">Affiliates</li>
                                                        <li class="text-slate-800 ml-6">Payment Processors, Creditors, etc.</li>
                                                        <li class="text-slate-800 ml-6">Service Providers</li>
                                                    </ul>
                                                </li>
                                                <li class="my-2">
                                                    <span class="text-black font-semibold">Categories of Third Parties to whom this type of Personal Information is Sold</span>
                                                    <p class="text-slate-800 py-8">We do not sell this personal information.</p>
                                                </li>
                                            </ol>
                                        </li>
                                        <li class="my-2">
                                            <h3 class="text-black text-lg font-medium underline pb-4">ONLINE & TECHNICAL INFORMATION</h3>
                                            <ol>
                                                <li class="my-2">
                                                    <span class="text-black font-semibold">Category and Sources of Personal Information</span>
                                                    <p class="text-slate-800 pt-8">We collect this type of information from sources as described in this Privacy Policy, including from:</p>
                                                    <ul class="list-disc space-y-2 py-8">
                                                        <li class="text-slate-800 ml-6">You </li>
                                                        <li class="text-slate-800 ml-6">Your computer or devices when you interact with the Site</li>
                                                        <li class="text-slate-800 ml-6">Automatically, via technologies such as cookies, web beacons, when you visit our Site</li>
                                                        <li class="text-slate-800 ml-6">Third parties, including security and advertising partners.</li>
                                                        <li class="text-slate-800 ml-6">We also associate information with you using unique identifiers collected from your devices or browsers.</li>
                                                    </ul>
                                                </li>
                                                <li class="my-2">
                                                    <span class="text-black font-semibold">Representative Information Elements</span>
                                                    <p class="text-slate-800 pt-8">Types of information in this category include:</p>
                                                    <ul class="list-disc space-y-2 py-8">
                                                        <li class="text-slate-800 ml-6">IP Address</li>
                                                        <li class="text-slate-800 ml-6">Address or other device identifiers or persistent identifiers</li>
                                                        <li class="text-slate-800 ml-6">Login ID</li>
                                                        <li class="text-slate-800 ml-6">Password</li>
                                                        <li class="text-slate-800 ml-6">Device characteristics (such as browser information)</li>
                                                        <li class="text-slate-800 ml-6">Web Server Logs</li>
                                                        <li class="text-slate-800 ml-6">Application Logs</li>
                                                    </ul>
                                                </li>
                                                <li class="my-2">
                                                    <span class="text-black font-semibold">Purpose for Collecting and Sharing the PI</span>
                                                    <p class="text-slate-800 pt-8">We use personal information as described in this Privacy Policy, including:</p>
                                                    <ul class="list-disc space-y-2 py-8">
                                                        <li class="text-slate-800 ml-6">For system administration, technology management, including optimizing our websites and experiences</li>
                                                        <li class="text-slate-800 ml-6">For information security and cybersecurity purposes, including detecting threats/spam</li>
                                                        <li class="text-slate-800 ml-6">For recordkeeping, including logs and records maintained as part of transaction information</li>
                                                        <li class="text-slate-800 ml-6">To better understand our customers and prospective customers and to enhance our Relationship Information, including by associating you with different devices and browsers that you may use</li>
                                                        <li class="text-slate-800 ml-6">For our everyday business purposes</li>
                                                    </ul>
                                                </li>
                                                <li class="my-2">
                                                    <span class="text-black font-semibold">Categories of Third Parties to whom this type of Personal Information is Disclosed for a Business Purpose</span>
                                                    <p class="text-slate-800 pt-8">We may disclose this type of information as described in this Privacy Policy, including to:</p>
                                                    <ul class="list-disc space-y-2 py-8">
                                                        <li class="text-slate-800 ml-6">Affiliates</li>
                                                        <li class="text-slate-800 ml-6">Service Providers</li>
                                                        <li class="text-slate-800 ml-6">Third parties who assist with our information technology and security programs</li>
                                                        <li class="text-slate-800 ml-6">Third parties who assist with fraud prevention, detection and mitigation</li>
                                                    </ul>
                                                </li>
                                                <li class="my-2">
                                                    <span class="text-black font-semibold">Categories of Third Parties to whom this type of Personal Information is Sold</span>
                                                    <p class="text-slate-800 py-8">We do not sell this personal information.</p>
                                                </li>
                                            </ol>
                                        </li>
                                    </ol>
                                </li>
                                <li class="my-2">
                                    <h2 class="text-black text-lg font-semibold pb-4">B. &nbsp;  Privacy Policy - Other Terms</h2>
                                    <ol>
                                        <li class="my-2">
                                            <h3 class="text-black text-lg font-medium underline pb-4">OTHER DISCLOSURE</h3>
                                            <p class="text-slate-800 pb-8 font-medium">Notwithstanding anything herein to the contrary, we may disclose personal information if, in good faith, we believe that such disclosure is necessary</p>
                                            <ol class="list-disc space-y-2 pb-8">
                                                <li class="text-slate-800 ml-6">to comply with relevant laws or to respond to discovery requests, subpoenas, or warrants served on us; </li>
                                                <li class="text-slate-800 ml-6">in connection with any legal investigation; </li>
                                                <li class="text-slate-800 ml-6">to protect or defend the rights or property of YN®® or users of the Services; </li>
                                                <li class="text-slate-800 ml-6">to investigate or assist in preventing any violation or potential violation of law or this Agreement; or</li>
                                                <li class="text-slate-800 ml-6">if we believe that an emergency involving the danger of death or serious physical injury to any person requires or justifies disclosure of information.</li>
                                            </ol>
                                        </li>
                                        <li class="my-2">
                                            <h3 class="text-black text-lg font-medium underline pb-4">WAYS YOU CAN ACCESS, CONTROL, AND CORRECT YOUR ACCOUNT INFORMATION</h3>
                                            <p class="text-justify text-slate-800 pb-8 font-medium">You can see, review, and change most of your Account Information by signing into your account. Please, update your personal information immediately if it changes or is inaccurate.</p>
                                            <p class="text-justify text-slate-800 pb-8 font-medium">We will honor any legal right you might have to access, modify, or erase your personal information. To request access and to find out whether any fees may apply, if permitted by applicable laws, please contact us at the following email address: <a href="mailto:HELP@YouNegotiate.com" class="text-primary hover:underline" target="_blank">HELP@YouNegotiate.com</a>.</p>
                                            <p class="text-justify text-slate-800 pb-8">Where you have a statutory right to request access or request the modification or erasure of your personal information, we can still withhold that access or decline to modify or erase your personal information pursuant to applicable national laws.</p>
                                            <p class="text-justify text-slate-800 pb-8">Upon your request (which can be done via your account settings), we will close your account and remove your personal information from view as soon as reasonably possible, based on your account activity and in accordance with applicable laws; provided that any transactions initiated prior to such closure will still be processed.</p>
                                        </li>
                                        <li class="my-2">
                                            <h3 class="text-black text-lg font-semibold underline pb-4">NON-IDENTIFIABLE DATA</h3>
                                            <p class="text-slate-800 pb-8">Subject to applicable laws, notwithstanding anything herein to the contrary, we have the right to use non-identifiable data (including aggregated data) in any manner see fit.</p>
                                        </li>
                                        <li class="my-2">
                                            <h3 class="text-black text-lg font-semibold underline pb-4">STORAGE DURATION AND ERASURE</h3>
                                            <p class="text-slate-800 pb-8">Your personal information will be stored by us in accordance with the Privacy Policy.  Subsequently, we will delete your personal information in accordance with our data retention and deletion policy or take steps to properly render the data anonymous, unless we are legally obliged to keep your personal data longer (e.g. for tax, accounting or auditing purposes). </p>
                                        </li>
                                        <li class="my-2">
                                            <h3 class="text-black text-lg font-semibold underline pb-4">DATA SECURITY</h3>
                                            <p class="text-slate-800 pb-8">We protect your personal data through technical and organizational security measures to minimize risks associated with data loss, misuse, unauthorized access and unauthorized disclosure and alteration. To this end we use firewalls and data encryption, for example, as well as authorization controls for data access.</p>
                                        </li>
                                        <li class="my-2">
                                            <h3 class="text-black text-lg font-semibold underline pb-4">CALIFORNIA PRIVACY RIGHTS</h3>
                                            <p class="text-slate-800 pb-8">Under the California Consumer Privacy Act (CCPA), if you are a California resident, you have the rights set forth in this section.  Any terms defined in the CCPA have the same meaning as used within this section.</p>
                                            <ol class="pb-8">
                                                <li class="my-2">
                                                    <span class="text-black text-base font-semibold">Right to Know</span>
                                                    <p class="text-justify text-slate-800 py-4">You have the right to know the categories of personal information that we collected and shared for a business purpose during the prior 12 months. Please see the above information regarding what we collect and how it is shared.</p>
                                                </li>
                                                <li class="my-2">
                                                    <span class="text-black text-base font-semibold">
                                                        Right to Access/Disclosure Right
                                                    </span>
                                                    <p class="text-justify text-slate-800 pt-4"> You have the right to request a disclosure of the personal information collected and shared about you over the past 12 months and the purpose for doing so. Upon submission of a verifiable consumer request, we will provide you with the following information.</p>
                                                    <ul class="text-slate-800 list-disc py-8">
                                                        <li class="ml-6">The categories of personal information we collected about you.</li>
                                                        <li class="ml-6">The categories of sources for the personal information we collected about you.</li>
                                                        <li class="ml-6">Our business or commercial purpose for collecting or selling that personal information.</li    >
                                                        <li class="ml-6">The categories of third parties with whom we share that personal information.</li>
                                                        <li class="ml-6">The specific pieces of personal information we collected about you.</li>
                                                        <li class="ml-6">A list that includes the personal information that we disclosed for a business purpose, identifying the category of personal information that each category of recipient obtained; and</li>
                                                    </ul>
                                                    <p class="text-slate-800 pb-4">If we provide this information to you electronically, the information will be in a portable format. To the extent that it is technically feasible, we will provide you the information in a readily useable format that you can easily transfer to another entity.</p>
                                                </li>
                                                <li class="my-2">
                                                    <span class="text-black text-base font-semibold">Right to Deletion</span>
                                                    <p class="text-justify text-slate-800 py-4"> If we collected personal information from you, you have the right to request the deletion of this personal information.</p>
                                                </li>
                                                <li class="my-2">
                                                    <span class="text-black text-base font-semibold ">Right to Be Free from Discrimination</span>
                                                    <p class="text-justify text-slate-800 pt-4"> California residents have the right to be free from discrimination for exercising any of the California consumer privacy rights. If you choose to exercise any of your CCPA rights, we will not:</p>
                                                    <ul class="text-slate-800 list-disc py-8">
                                                        <li class="ml-6">Deny you the Services;</li>
                                                        <li class="ml-6">Charge you a different price or rate for goods or services, including through granting discounts or other benefits, or imposing penalties;</li>
                                                        <li class="ml-6">Provide you a different level or quality of Services; or</li>
                                                        <li class="ml-6">Suggest that you may receive a different price or rate for Services or a different level or quality of Services.</li>
                                                    </ul>
                                                    <p class="text-md text-slate-800 font-medium">If you have any questions or requests regarding your rights as a California resident, please contact us at the following email address - <a href="mailto:help@YouNegotiate.com" target="_blank" class="text-primary hover:underline">help@YouNegotiate.com</a>.</p>
                                                </li>
                                            </ol>
                                        </li>
                                    </ol>
                                </li>
                            </ol>
                        </li>
                    </ol>
                </div>
            </div>
        </div>

        <x-slot name="footer">
            <div class="mt-3 text-center">
                <x-dialog.close>
                    <x-form.default-button 
                        type="button" 
                        class="w-24 sm:text-base font-medium"
                    >
                        {{ __('Close') }}
                    </x-form.default-button>
                </x-dialog.close>
            </div>
        </x-slot>
    </x-dialog.panel>
</x-dialog>
