<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Queue\Middleware\WithoutOverlapping;

use Illuminate\Support\Facades\DB;
use App\Models\DocumentChunk;
use Illuminate\Support\Str;
use Illuminate\Bus\Queueable;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Services\ContractChunker;
use Illuminate\Support\Facades\Log;

class IngestContractJob implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;
    public $backoff = [10, 30]; //wait 10s, then 30s between retries
    public $uniqueFor = 600; // lock expires after 10 min (safety, if a job dies)
    /**
     * Create a new job instance.
     */
    public function __construct(public int $tenant_id)
    {
        
    }

    public function uniqueId(): string
    {
        // Per-tenant key: one tenant's ingest must not dedupe/block another's.
        return "ingest-contract-{$this->tenant_id}";
    }

    public function middleware(): array
    {
        return [new WithoutOverlapping($this->uniqueId())];
    }

    /**
     * Execute the job.
     */
    public function handle(ContractChunker $chunker): void
    {
        $contract = <<<'TEXT'
        MASTER SERVICES AGREEMENT

        This Master Services Agreement ("Agreement") is entered into as of January 15, 2024 (the "Effective Date") by and between Acme Logistics, Inc., a Delaware corporation with its principal place of business at 100 Harbor Way, Wilmington, DE ("Client"), and Brightline Consulting LLC, a California limited liability company with its principal place of business at 500 Market Street, San Francisco, CA ("Consultant"). Client and Consultant are each referred to herein as a "Party" and collectively as the "Parties."

        1. SCOPE OF SERVICES

        Consultant shall provide professional consulting services to Client as described in one or more statements of work executed by both Parties from time to time, each a "Statement of Work" or "SOW." Each SOW shall specify the services to be performed, the deliverables (if any), the timeline, and the fees payable. In the event of any conflict between the terms of this Agreement and a SOW, the terms of this Agreement shall control unless the SOW expressly states that it is amending a specific provision of this Agreement.

        Consultant shall perform the services in a professional and workmanlike manner consistent with generally accepted industry standards. Consultant shall use commercially reasonable efforts to meet any timelines set forth in an applicable SOW, provided that such timelines are estimates only and not firm commitments unless explicitly designated as a "Milestone Date" within the SOW. Client acknowledges that delays caused by Client's failure to provide timely feedback, access, or materials may extend any estimated timeline.

        2. COMPENSATION AND PAYMENT TERMS
        
        The total fees payable under this Agreement are USD $99,000. In consideration for the services performed under this Agreement, Client shall pay Consultant the fees set forth in the applicable SOW. Unless otherwise specified, Consultant shall invoice Client on a monthly basis for services rendered during the preceding month. All invoices are due and payable within thirty (30) days of the invoice date. Any amount not paid when due shall accrue interest at the rate of 1.5% per month, or the maximum rate permitted by applicable law, whichever is lower.

        Client shall reimburse Consultant for reasonable, pre-approved travel and out-of-pocket expenses incurred in connection with the performance of services, provided that Consultant submits documentation of such expenses within sixty (60) days of incurring them. Consultant shall not be obligated to continue performing services under any SOW if Client's account becomes more than forty-five (45) days past due, and Consultant may suspend services upon five (5) business days' written notice to Client without liability for any resulting delay.

        3. TERM AND TERMINATION

        This Agreement shall commence on the Effective Date and shall continue in effect until terminated by either Party in accordance with this Section 3. Either Party may terminate this Agreement for convenience upon sixty (60) days' prior written notice to the other Party. Either Party may terminate this Agreement immediately upon written notice if the other Party materially breaches this Agreement and fails to cure such breach within thirty (30) days after receiving written notice describing the breach in reasonable detail.

        Upon termination of this Agreement for any reason, Client shall pay Consultant for all services performed and expenses incurred up to the effective date of termination. Sections 4 (Confidentiality), 5 (Intellectual Property), 6 (Limitation of Liability), and 8 (General Provisions) shall survive any termination or expiration of this Agreement, along with any other provision that by its nature is intended to survive.

        4. CONFIDENTIALITY

        Each Party may disclose to the other Party certain confidential or proprietary information ("Confidential Information"). The receiving Party shall use the disclosing Party's Confidential Information solely for purposes of performing its obligations under this Agreement, and shall not disclose such information to any third party except to employees, contractors, or agents who have a need to know such information and who are bound by confidentiality obligations at least as protective as those contained herein.

        Confidential Information does not include information that: (a) was already known to the receiving Party prior to disclosure without an obligation of confidentiality; (b) is or becomes publicly available through no fault of the receiving Party; (c) is independently developed by the receiving Party without use of the disclosing Party's Confidential Information; or (d) is rightfully obtained by the receiving Party from a third party without restriction. The obligations of confidentiality set forth herein shall continue for a period of three (3) years following the disclosure of the applicable Confidential Information, except with respect to trade secrets, which shall remain protected for so long as they qualify as trade secrets under applicable law.

        5. INTELLECTUAL PROPERTY

        Except as otherwise set forth in an applicable SOW, all deliverables specifically created by Consultant for Client under this Agreement ("Deliverables") shall be deemed "work made for hire" to the extent permitted under applicable copyright law. To the extent any Deliverable is not deemed a work made for hire, Consultant hereby assigns to Client all right, title, and interest in and to such Deliverable upon full payment of all fees associated with its creation.

        Notwithstanding the foregoing, Consultant retains all right, title, and interest in and to any pre-existing tools, frameworks, methodologies, templates, or know-how used by Consultant in performing the services ("Consultant Background IP"), and grants to Client a non-exclusive, perpetual, royalty-free license to use any Consultant Background IP that is incorporated into a Deliverable, solely as necessary for Client to use such Deliverable for its intended purpose.

        6. LIMITATION OF LIABILITY

        EXCEPT FOR BREACHES OF SECTION 4 (CONFIDENTIALITY), A PARTY'S INDEMNIFICATION OBLIGATIONS, OR A PARTY'S GROSS NEGLIGENCE OR WILLFUL MISCONDUCT, IN NO EVENT SHALL EITHER PARTY BE LIABLE TO THE OTHER PARTY FOR ANY INDIRECT, INCIDENTAL, CONSEQUENTIAL, SPECIAL, OR PUNITIVE DAMAGES ARISING OUT OF OR RELATED TO THIS AGREEMENT, EVEN IF SUCH PARTY HAS BEEN ADVISED OF THE POSSIBILITY OF SUCH DAMAGES.

        EXCEPT FOR BREACHES OF SECTION 4, EACH PARTY'S INDEMNIFICATION OBLIGATIONS UNDER SECTION 7, OR DAMAGES ARISING FROM A PARTY'S GROSS NEGLIGENCE OR WILLFUL MISCONDUCT, EACH PARTY'S TOTAL CUMULATIVE LIABILITY ARISING OUT OF OR RELATED TO THIS AGREEMENT SHALL NOT EXCEED THE TOTAL FEES PAID OR PAYABLE BY CLIENT TO CONSULTANT UNDER THE APPLICABLE SOW IN THE TWELVE (12) MONTHS PRECEDING THE EVENT GIVING RISE TO THE CLAIM.

        7. INDEMNIFICATION

        Consultant shall indemnify, defend, and hold harmless Client from and against any third-party claims, damages, liabilities, and reasonable expenses (including attorneys' fees) arising out of Consultant's gross negligence, willful misconduct, or material breach of this Agreement. Client shall indemnify, defend, and hold harmless Consultant from and against any third-party claims, damages, liabilities, and reasonable expenses (including attorneys' fees) arising out of Client's gross negligence, willful misconduct, or material breach of this Agreement, or Client's misuse of any Deliverable in a manner not authorized under this Agreement.

        The indemnified Party shall promptly notify the indemnifying Party in writing of any claim for which indemnification is sought, provided that failure to provide prompt notice shall not relieve the indemnifying Party of its obligations except to the extent it is materially prejudiced by such failure. The indemnifying Party shall have the right to control the defense and settlement of any such claim, provided that it shall not settle any claim in a manner that imposes liability or admission of fault on the indemnified Party without the indemnified Party's prior written consent.

        8. GENERAL PROVISIONS

        This Agreement, together with any SOWs executed hereunder, constitutes the entire agreement between the Parties with respect to its subject matter and supersedes all prior or contemporaneous agreements, understandings, and communications, whether written or oral. This Agreement may be amended only by a written instrument signed by authorized representatives of both Parties. No waiver of any provision of this Agreement shall be effective unless in writing and signed by the Party against whom the waiver is sought to be enforced.

        Neither Party may assign this Agreement without the prior written consent of the other Party, except that either Party may assign this Agreement without consent in connection with a merger, acquisition, or sale of substantially all of its assets. This Agreement shall be governed by and construed in accordance with the laws of the State of Delaware, without regard to its conflict of laws principles. Any dispute arising out of or relating to this Agreement shall be resolved exclusively in the state or federal courts located in Wilmington, Delaware, and each Party consents to the personal jurisdiction of such courts.

        If any provision of this Agreement is held to be invalid or unenforceable, the remaining provisions shall continue in full force and effect, and the invalid or unenforceable provision shall be reformed to the minimum extent necessary to make it enforceable while preserving the original intent of the Parties. This Agreement may be executed in counterparts, each of which shall be deemed an original, and all of which together shall constitute one and the same instrument.

        All notices required or permitted under this Agreement shall be in writing and shall be deemed given when delivered personally, sent by confirmed email, or sent by nationally recognized overnight courier to the address of the receiving Party set forth in the preamble to this Agreement, or to such other address as either Party may designate by written notice to the other in accordance with this Section. Notice shall be deemed effective on the date of delivery if delivered by hand or courier, or on the next business day if delivered by email with confirmation of transmission received during normal business hours.

        Neither Party shall be liable for any failure or delay in the performance of its obligations under this Agreement (other than payment obligations) to the extent such failure or delay is caused by circumstances beyond its reasonable control, including but not limited to acts of God, natural disaster, war, terrorism, riot, embargo, governmental action, labor dispute, fire, flood, or failure of third-party telecommunications or internet infrastructure ("Force Majeure Event"). The Party affected by a Force Majeure Event shall promptly notify the other Party of the nature and expected duration of the event and shall use commercially reasonable efforts to mitigate its effects and resume performance as soon as reasonably practicable.

        Nothing in this Agreement, express or implied, is intended to or shall confer upon any person or entity other than the Parties and their respective successors and permitted assigns any right, benefit, or remedy of any nature whatsoever under or by reason of this Agreement. Each Party represents and warrants that it has the full right, power, and authority to enter into this Agreement and to perform its obligations hereunder, and that its execution and performance of this Agreement does not and will not conflict with or violate any other agreement to which it is a party.

        Each Party agrees, at the request and expense of the other Party, to execute and deliver any additional documents and to take any further actions as may be reasonably necessary to give full effect to the terms and intent of this Agreement. The headings used in this Agreement are for reference and convenience only and shall not affect the interpretation or construction of any provision herein. Any reference to "days" in this Agreement shall mean calendar days unless otherwise expressly stated to mean business days.
        TEXT;

        $chunks = $chunker->chunk($contract);

        Log::info('Chunker produced ' . count($chunks) . ' chunks');
        try {
            DB::transaction(function() use($chunks){
            // Only clear THIS tenant's chunks — never touch other tenants' data.
            DocumentChunk::where('tenant_id', $this->tenant_id)->delete();
            foreach ($chunks as $chunk) {
                    // Embed this chunk's text (you know this line
                    // from Day 10)
                    $embedding = Str::of($chunk['text'])->toEmbeddings();
                    DocumentChunk::create([
                        'content'     => $chunk['text'],
                        'token_count' => (int) ceil(mb_strlen($chunk['text']) / 4),
                        'embedding'   => $embedding,
                        'tenant_id'   => $this->tenant_id
                    ]);

                    Log::info('Stored: ' . substr($chunk['text'], 0, 50) . '...');
                }
            });  
        } catch (\InvalidArgumentException $e) {
            $this->fail($e); // permanent — stop now, don't retry
        }              
    }
}
