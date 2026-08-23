import 'package:flutter/material.dart';
import 'package:get/get.dart';
import 'package:qrpay/backend/model/common/common_success_model.dart';
import 'package:qrpay/backend/model/remittance/remittance_get_recipient_model.dart';
import 'package:qrpay/routes/routes.dart';

import '../../../backend/model/remittance/remittance_info_model.dart';
import '../../../backend/services/api_services.dart';
import '../../../language/english.dart';
import '../../../widgets/others/congratulation_widget.dart';
import '../remaining_balance_controller/ramaining_controller.dart';

class RemittanceController extends GetxController {
  final sendingCountryController = TextEditingController();
  final receivingCountryController = TextEditingController();
  final recipeintController = TextEditingController();
  final receivingMethodController = TextEditingController();
  final remainingController = Get.put(RemaingBalanceController());

  // final Controller = TextEditingController();
  final amountController = TextEditingController();
  final recipientGetController = TextEditingController();

  RxString selectedSendingCountry = "".obs;
  RxString selectedReceivingCountry = "".obs;
  RxString selectedSendingCountryCode = "".obs;
  RxString selectedReceivingCountryCode = "".obs;
  RxString selectedMethod = "Select Method".obs;
  RxString selectedTrxType = "".obs;
  RxString selectedRecipient = "Select Recipient".obs;
  RxString baseCurrency = "".obs;
  RxDouble baseCurrencyRate = 0.0.obs;
  RxInt selectedRecipientId = 0.obs;

  RxDouble toCountriesRate = 0.0.obs;
  RxDouble fromCountriesRate = 0.0.obs;
 RxDouble dailyLimit = 0.0.obs;
  RxDouble monthlyLimit = 0.0.obs;
  RxInt sendingCountryId = 0.obs;
  RxInt receivingCountryId = 0.obs;
  RxDouble fixedCharge = 0.0.obs;
  RxDouble percentCharge = 0.0.obs;
  RxDouble minLimit = 0.0.obs;
  RxDouble maxLimit = 0.0.obs;
  RxDouble totalFee = 0.0.obs;

  List<Country> sendingCountryList = [];
  List<Country> receivingCountryList = [];
  List<TransactionType> transactionTypeList = [];

  RxList<RecipientInfo> recipientList = <RecipientInfo>[].obs;

  void togoRemittancePreview() {
    Get.toNamed(Routes.remittancePreviewScreen);
  }

  @override
  void onInit() {
    amountController.text= "0";
    getRemittanceInfo();
    super.onInit();
  }

  // ---------------------------- RemittanceInfoModel ------------------
  // api loading process indicator variable
  final _isLoading = false.obs;

  bool get isLoading => _isLoading.value;

  late RemittanceInfoModel _remittanceInfoModel;

  RemittanceInfoModel get remittanceInfoModel => _remittanceInfoModel;

  // --------------------------- Api function ----------------------------------
  // get RemittanceInfo function
  Future<RemittanceInfoModel> getRemittanceInfo() async {
    _isLoading.value = true;
    update();

    await ApiServices.remittanceInfoAPi().then((value) {
      _remittanceInfoModel = value!;
      var data = _remittanceInfoModel.data;

      sendingCountryList = data.fromCountry;
      receivingCountryList = data.toCountries;
      transactionTypeList = data.transactionTypes;

      baseCurrency.value = data.userWallet.currency;

      selectedSendingCountry.value = data.fromCountry.first.country;
      selectedReceivingCountry.value = data.toCountries.first.country;

      toCountriesRate.value = data.toCountries.first.rate;
      fromCountriesRate.value = data.fromCountry.first.rate;

      sendingCountryId.value = data.fromCountry.first.id;
      receivingCountryId.value = data.toCountries.first.id;
      selectedSendingCountryCode.value = data.fromCountry.first.code;
      selectedReceivingCountryCode.value = data.toCountries.first.code;

      if (data.recipients.isNotEmpty) {
        selectedMethod.value = data.recipients.first.trxTypeName;
        selectedTrxType.value = data.recipients.first.trxType;
        selectedRecipientId.value = data.recipients.first.id;
        remittanceGetRecipientProcess();
      }

      // Remittance Charge
      var remittanceCharge = data.remittanceCharge;
      fixedCharge.value = remittanceCharge.fixedCharge;
      percentCharge.value = remittanceCharge.percentCharge;
      minLimit.value = remittanceCharge.minLimit;
      maxLimit.value = remittanceCharge.maxLimit;
      monthlyLimit.value = remittanceCharge.monthlyLimit;
      dailyLimit.value = remittanceCharge.dailyLimit;

            //start remaing get
      remainingController.transactionType.value =
          _remittanceInfoModel.data.getRemainingFields.transactionType;
      remainingController.attribute.value =
          _remittanceInfoModel.data.getRemainingFields.attribute;
      remainingController.cardId.value =
          _remittanceInfoModel.data.remittanceCharge.id;
      remainingController.senderAmount.value = amountController.text;
      remainingController.senderCurrency.value =
          _remittanceInfoModel.data.userWallet.currency;

      remainingController.getRemainingBalanceProcess();

      update();
    }).catchError((onError) {
      log.e(onError);
    });

    _isLoading.value = false;
    update();
    return _remittanceInfoModel;
  }

  //  remittance confirm function

  final _isRemittanceConfirm = false.obs;

  bool get isRemittanceConfirm => _isRemittanceConfirm.value;

  late CommonSuccessModel _remittanceConfirmModel;

  CommonSuccessModel get remittanceConfirmModel => _remittanceConfirmModel;

  Future<CommonSuccessModel> remittanceConfirmProcess(
      BuildContext context) async {
    _isRemittanceConfirm.value = true;
    update();

    Map<String, dynamic> inputBody = {
      'form_country': sendingCountryId.value,
      'to_country': receivingCountryId.value,
      'transaction_type': selectedTrxType.value,
      'recipient': selectedRecipientId.value,
      'send_amount': amountController.text,
      'receive_amount': recipientGetController.text,
    };

    await ApiServices.remittanceConfirmAPi(body: inputBody).then((value) {
      _remittanceConfirmModel = value!;
      update();
      if (context.mounted) {
        StatusScreen.show(
          context: context,
          subTitle: Strings.sendMoneySuccesfully.tr,
          onPressed: () {
            Get.offAllNamed(
              Routes.bottomNavBarScreen,
            );
          },
        );
      }
    }).catchError((onError) {
      log.e(onError);
      _isRemittanceConfirm.value = false;
    });
    _isRemittanceConfirm.value = false;
    update();
    return _remittanceConfirmModel;
  }

  // Remittance Get Recipient function

  final _isGetRemittance = false.obs;

  bool get isGetRemittance => _isGetRemittance.value;

  late RemittanceGetRecipientModel _getRemittanceModel;

  RemittanceGetRecipientModel get getRemittanceModel => _getRemittanceModel;

  Future<RemittanceGetRecipientModel> remittanceGetRecipientProcess() async {
    _isGetRemittance.value = true;
    recipientList.clear();
    selectedRecipient.value = "No Recipient";
    update();

    Map<String, dynamic> inputBody = {
      'to_country': receivingCountryId.value,
      'transaction_type': selectedTrxType.value,
    };

    await ApiServices.remittanceGetRecipientAPi(body: inputBody).then((value) {
      _getRemittanceModel = value!;
      var name = _getRemittanceModel.data.recipient.first;

      recipientList.value = _getRemittanceModel.data.recipient;
      selectedRecipient.value = "${name.firstname} ${name.lastname}";
      selectedRecipientId.value = _getRemittanceModel.data.recipient.first.id;
      update();
    }).catchError((onError) {
      log.e(onError);
      _isGetRemittance.value = false;
    });
    _isGetRemittance.value = false;
    update();
    return _getRemittanceModel;
  }

  // Currency exchange method

  get recipientGet => _recipientGetOnChange();

  get senderSendAmount => _senderSendAmount();

  _senderSendAmount() {
    var amount = _doubleParse(recipientGetController.text);

    amountController.text =
        (amount / fromCountriesRate.value).toStringAsFixed(2);
  }

  _recipientGetOnChange() {
    var amount = _doubleParse(amountController.text);

    recipientGetController.text =
        (amount * toCountriesRate.value).toStringAsFixed(2);
  }

  _doubleParse(amount) {
    return double.parse(amount.isNotEmpty ? amount : '0.0');
  }

  RxDouble getFee({required double rate}) {
    double value = fixedCharge.value * rate;
    value = value +
        (double.parse(
                amountController.text.isEmpty ? '0.0' : amountController.text) *
            (percentCharge.value / 100));

    if (amountController.text.isEmpty) {
      totalFee.value = 0.0;
    } else {
      totalFee.value = value;
    }

    debugPrint(totalFee.value.toStringAsPrecision(2));
    return totalFee;
  }
}
