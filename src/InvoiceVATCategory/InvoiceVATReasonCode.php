<?php


namespace Malik12tree\ZATCA\InvoiceVATCategory;

use Malik12tree\ZATCA\Utils\Enum7;

class InvoiceVATReasonCode extends Enum7
{

	/** Financial services mentioned in Article 29 of the VAT Regulations */
	const E_29 = "VATEX-SA-29";

	/** Life insurance services mentioned in Article 29 of the VAT */
	const E_29_7 = "VATEX-SA-29-7";

	/** Real estate transactions mentioned in Article 30 of the VAT */
	const E_30 = "VATEX-SA-30";


	/** Export of goods */
	const Z_32 = "VATEX-SA-32";

	/** Export of services */
	const Z_33 = "VATEX-SA-33";

	/** The international transport of Goods  */
	const Z_34_1 = "VATEX-SA-34-1";

	/** The international transport of passengers */
	const Z_34_2 = "VATEX-SA-34-2";

	/** Services directly connected and incidental to a Supply of international passenger transport */
	const Z_34_3 = "VATEX-SA-34-3";

	/** Supply of a qualifying means of transport */
	const Z_34_4 = "VATEX-SA-34-4";

	/** Any services relating to Goods or passenger transportation, as defined in article twenty five of these Regulations */
	const Z_34_5 = "VATEX-SA-34-5";

	/** Medicines and medical equipment */
	const Z_35 = "VATEX-SA-35";

	/** Qualifying metals */
	const Z_36 = "VATEX-SA-36";

	/** Private education to citizen */
	const Z_EDU = "VATEX-SA-EDU";

	/** Private healthcare to citizen */
	const Z_HEA = "VATEX-SA-HEA";
}
