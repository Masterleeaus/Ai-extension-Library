import 'package:intl/intl.dart';
import 'package:qrpay/backend/model/common/common_success_model.dart';
import 'package:qrpay/utils/basic_screen_imports.dart';

import '../../../../backend/local_storage/local_storage.dart';
import '../../../../backend/model/categories/virtual_card/virtual_card_flutter_wave/card_transaction_model.dart';
import '../../../../backend/model/categories/virtual_card/virtual_card_sudo/sudo_card_details_model.dart';
import '../../../../backend/model/categories/virtual_card/virtual_card_sudo/virtual_card_sudo_info_model.dart';
import '../../../../backend/services/api_services.dart';
import '../../../../backend/utils/logger.dart';
import '../../../../routes/routes.dart';
import '../../../navbar/dashboard_controller.dart';
import '../../remaining_balance_controller/ramaining_controller.dart';

final log = logger(VirtualSudoCardController);

class VirtualSudoCardController extends GetxController {
  final fundAmountController = TextEditingController();
  final fromController = TextEditingController();
  RxString baseCurrency = "".obs;

  final remainingController = Get.put(RemaingBalanceController());
  List<String> baseCurrencyList = [];
  final dashboardController = Get.find<DashBoardController>();
  RxDouble fee = 0.0.obs;
  RxDouble limitMin = 0.0.obs;
  RxDouble limitMax = 0.0.obs;
  RxDouble percentCharge = 0.0.obs;
  RxDouble fixedCharge = 0.0.obs;
  RxDouble rate = 0.0.obs;
  RxDouble dailyLimit = 0.0.obs;
  RxDouble monthlyLimit = 0.0.obs;
  RxDouble totalFee = 0.0.obs;
  RxInt activeIndex = 0.obs;
  RxInt activeIndicatorIndex = 0.obs;
  RxDouble totalCharge = 0.00.obs;
  RxDouble totalPay = 0.00.obs;
  RxString cardImage = ''.obs;
  RxString cardBackDetails = ''.obs;
  RxString siteTitle = ''.obs;
  RxString cardId = ''.obs;
  RxDouble exchangeRate2 = 0.0.obs;
  RxString siteLogo = ''.obs;
  Rxn<UserWallet> selectMainWallet = Rxn<UserWallet>();
  List<UserWallet> walletsList = [];
  List<SupportedCurrency> supportedCurrencyList = [];
  Rxn<SupportedCurrency> selectedSupportedCurrency = Rxn<SupportedCurrency>();
  RxString selectedCurrencyCode = ''.obs;
  @override
  void onInit() {
    fundAmountController.text = "0";
    if (LocalStorages.getCardType() == 'sudo') {
      getCardInfoData();
      ();
    }
    super.onInit();
  }

  changeIndicator(int value) {
    activeIndicatorIndex.value = value;
  }

  String getDay(String value) {
    DateTime startDate = DateTime.parse(value);
    var date = DateFormat('dd').format(startDate);
    return date.toString();
  }

  String getMonth(String value) {
    DateTime startDate = DateTime.parse(value);
    var date = DateFormat('MMMM').format(startDate);
    return date.toString();
  }

  // ---------------------------------------------------------------------------
  //                              Get Card Info Data
  // ---------------------------------------------------------------------------

  // -------------------------------Api Loading Indicator-----------------------
  //
  final _isLoading = false.obs;

  bool get isLoading => _isLoading.value;

  // -------------------------------Define API Model-----------------------------
  //
  late VirtualCardSudoInfoModel _cardInfoModel;

  VirtualCardSudoInfoModel get cardInfoModel => _cardInfoModel;

  // ------------------------------API Function---------------------------------
  //
  Future<VirtualCardSudoInfoModel> getCardInfoData() async {
    _isLoading.value = true;
    update();

    await ApiServices.sudoCardInfoApi().then((value) {
      _cardInfoModel = value!;
      selectedSupportedCurrency.value =
          _cardInfoModel.data.supportedCurrency.first;
      selectedCurrencyCode.value =
          _cardInfoModel.data.supportedCurrency.first.currencyCode;
      cardId.value = _cardInfoModel.data.myCard.first.cardId;
      for (var v in _cardInfoModel.data.supportedCurrency) {
        supportedCurrencyList.add(SupportedCurrency(
          id: v.id,
          name: v.name,
          mobileCode: v.mobileCode,
          currencyName: v.currencyName,
          currencyCode: v.currencyCode,
          currencySymbol: v.currencySymbol,
          rate: v.rate,
          status: v.status,
        ));
      }
      dailyLimit.value = _cardInfoModel.data.cardCharge.dailyLimit;

      monthlyLimit.value = _cardInfoModel.data.cardCharge.monthlyLimit;

      baseCurrency.value = _cardInfoModel.data.baseCurr;
      baseCurrencyList.add(_cardInfoModel.data.baseCurr);

      limitMin.value = _cardInfoModel.data.cardCharge.minLimit;
      limitMax.value = _cardInfoModel.data.cardCharge.maxLimit;
      percentCharge.value = _cardInfoModel.data.cardCharge.percentCharge;
      fixedCharge.value = _cardInfoModel.data.cardCharge.fixedCharge;

      rate.value = 1.0;
      exchangeRate2.value = _cardInfoModel.data.baseCurrRate /
          _cardInfoModel.data.userWallet.first.currency.rate;
      cardImage.value = _cardInfoModel.data.cardBasicInfo.cardBg;
      cardBackDetails.value = _cardInfoModel.data.cardBasicInfo.cardBackDetails;
      siteTitle.value = _cardInfoModel.data.cardBasicInfo.siteTitle;
      siteLogo.value = _cardInfoModel.data.cardBasicInfo.siteLogo;

      fromController.text =
          "${_cardInfoModel.data.userWallet.first.currency.name} (${_cardInfoModel.data.userWallet.first.balance} ${_cardInfoModel.data.userWallet.first.currency.code})";

      //start remaing get
      remainingController.transactionType.value =
          _cardInfoModel.data.getRemainingFields.transactionType;
      remainingController.attribute.value =
          _cardInfoModel.data.getRemainingFields.attribute;
      remainingController.cardId.value = _cardInfoModel.data.cardCharge.id;
      remainingController.senderAmount.value = fundAmountController.text;
      remainingController.senderCurrency.value = _cardInfoModel.data.baseCurr;
      remainingController.extraRate.value = exchangeRate2.value;
      remainingController.getRemainingBalanceProcess();
      update();
    }).catchError((onError) {
      log.e(onError);
    });

    _isLoading.value = false;
    update();
    return _cardInfoModel;
  }

  // ---------------------------------------------------------------------------
  //                              Get Card Details Data
  // ---------------------------------------------------------------------------

  // -------------------------------Api Loading Indicator-----------------------
  //
  final _isDetailsLoading = false.obs;

  bool get isDetailsLoading => _isDetailsLoading.value;

  // -------------------------------Define API Model-----------------------------
  //
  late SudoCardDetailsModel _cardDetailsModel;

  SudoCardDetailsModel get cardDetailsModel => _cardDetailsModel;

  // ------------------------------API Function---------------------------------
  //
  Future<SudoCardDetailsModel> getCardDetailsData(String id) async {
    _isDetailsLoading.value = true;
    update();

    await ApiServices.sudoCardDetailsApi(id: id).then((value) {
      _cardDetailsModel = value!;

      update();
    }).catchError((onError) {
      log.e(onError);
    });

    _isDetailsLoading.value = false;
    update();
    return _cardDetailsModel;
  }

  // ---------------------------------------------------------------------------
  //                              Card Block Process
  // ---------------------------------------------------------------------------

  // -------------------------------Define API Model-----------------------------
  //
  late CommonSuccessModel _cardBlockModel;

  CommonSuccessModel get cardBlockModel => _cardBlockModel;

  // ------------------------------API Function---------------------------------
  //
  Future<CommonSuccessModel> cardBlockProcess(String cardId) async {
    _isLoading.value = true;
    Map<String, dynamic> inputBody = {'card_id': cardId};

    update();

    await ApiServices.sudoCardBlockApi(body: inputBody).then((value) {
      _cardBlockModel = value!;
      getCardDetailsData(cardId);
      update();
    }).catchError((onError) {
      log.e(onError);
    });

    _isLoading.value = false;
    update();
    return _cardBlockModel;
  }

  // ---------------------------------------------------------------------------
  //                              Card Unblock Process
  // ---------------------------------------------------------------------------

  // -------------------------------Define API Model-----------------------------
  //

  late CommonSuccessModel _cardUnBlockModel;

  CommonSuccessModel get cardUnBlockModel => _cardUnBlockModel;

  // ------------------------------API Function---------------------------------
  //
  Future<CommonSuccessModel> cardUnBlockProcess(String cardId) async {
    _isLoading.value = true;
    Map<String, dynamic> inputBody = {'card_id': cardId};

    update();

    await ApiServices.sudoCardUnBlockApi(body: inputBody).then((value) {
      _cardUnBlockModel = value!;
      getCardDetailsData(cardId);
      update();
    }).catchError((onError) {
      log.e(onError);
    });

    _isLoading.value = false;
    update();
    return _cardUnBlockModel;
  }

  late CommonSuccessModel _cardFundModel;

  CommonSuccessModel get cardFundModel => _cardFundModel;

  // ------------------------------API Add Fund---------------------------------
  //

    final _isAddFundLoading = false.obs;

  bool get isAddFundLoading => _isAddFundLoading.value;
  Future<CommonSuccessModel> cardAddFundProcess() async {
    _isAddFundLoading.value = true;
    Map<String, dynamic> inputBody = {
      'card_id': cardId,
      'card_amount': fundAmountController.text,
      'currency': selectedCurrencyCode.value,
      'from_currency': baseCurrency.value,
    };

    update();

    await ApiServices.sudoAddFundApi(body: inputBody).then((value) {
      _cardFundModel = value!;

      update();
    }).catchError((onError) {
      log.e(onError);
    });

    _isAddFundLoading.value = false;
    update();
    return _cardFundModel;
  }

  final _isMakeDefaultLoading = false.obs;

  bool get isMakeDefaultLoading => _isMakeDefaultLoading.value;

  late CommonSuccessModel _cardDefaultModel;

  CommonSuccessModel get cardDefaultModel => _cardDefaultModel;

  // ------------------------------API Function---------------------------------
  //
  Future<CommonSuccessModel> makeCardDefaultProcess(String cardId) async {
    _isMakeDefaultLoading.value = true;
    Map<String, dynamic> inputBody = {'card_id': cardId};

    update();

    await ApiServices.sudoCardMakeOrRemoveDefaultApi(body: inputBody)
        .then((value) {
      _cardDefaultModel = value!;
      getCardInfoData();
      update();
    }).catchError((onError) {
      log.e(onError);
    });

    _isMakeDefaultLoading.value = false;
    update();
    return _cardDefaultModel;
  }

  // ---------------------------------------------------------------------------
  //                              Get Card Transaction Data
  // ---------------------------------------------------------------------------

  // -------------------------------Define API Model-----------------------------
  //
  late CardTransactionModel _cardTransactionModel;

  CardTransactionModel get cardTransactionModel => _cardTransactionModel;

  // ------------------------------API Function---------------------------------
  //
  Future<CardTransactionModel> getCardTransactionData(String id) async {
    _isLoading.value = true;
    update();
    Get.toNamed(Routes.transactionHistoryScreen);
    await ApiServices.cardTransactionApi(id: id).then((value) {
      _cardTransactionModel = value!;
      update();
    }).catchError((onError) {
      log.e(onError);
    });

    _isLoading.value = false;
    update();
    return _cardTransactionModel;
  }

  void buyCard() {}

  // -------------------------------Api Loading Indicator-----------------------

  late CommonSuccessModel _cardCreateData;

  CommonSuccessModel get cardCreateData => _cardCreateData;

  Future<CommonSuccessModel> cardCreateProcess(String cardAmount) async {
    _isLoading.value = true;
    Map<String, dynamic> inputBody = {
      'card_amount': cardAmount,
    };

    update();

    await ApiServices.createCardApi(body: inputBody).then((value) {
      _cardCreateData = value!;

      update();
    }).catchError((onError) {
      log.e(onError);
    });

    _isLoading.value = false;
    update();
    return _cardCreateData;
  }

  void calculation() {
    CardCharge data = _cardInfoModel.data.cardCharge;
    double amount = 0.0;

    if (fundAmountController.text.isNotEmpty) {
      try {
        amount = double.parse(fundAmountController.text);
      } catch (e) {
        // print('Error parsing double: $e');
      }
    }

    percentCharge.value = ((amount / 100) * data.percentCharge);
    totalCharge.value = (double.parse(data.fixedCharge.toString()) *
            selectMainWallet.value!.currency.rate) +
        percentCharge.value;

    totalPay.value = amount + totalCharge.value;
  }

  updateLimit() {
    var limit = _cardInfoModel.data.cardCharge;
    limitMax.value = limit.maxLimit * selectedSupportedCurrency.value!.rate;
    limitMin.value = limit.minLimit * selectedSupportedCurrency.value!.rate;
  }
}
